<?php

declare(strict_types=1);

namespace App\Support\JsonSchema;

use BackedEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\NumberType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\StringType;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rules\Enum as IlluminateEnumRule;
use ReflectionClass;
use Spatie\LaravelData\Attributes\Validation\Enum as EnumRule;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Contracts\BaseData;
use Spatie\LaravelData\Enums\DataTypeKind;
use Spatie\LaravelData\Support\DataClass;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\DataPropertyType;
use Spatie\LaravelData\Support\Types\NamedType;
use Spatie\LaravelData\Support\Validation\References\ExternalReference;
use Spatie\LaravelData\Support\Validation\ValidationRule;

/**
 * Converts a Spatie Laravel Data DTO class into an Illuminate JsonSchema ObjectType.
 *
 * Walks the DTO's properties recursively: maps scalars (string, int, float, bool), BackedEnum
 * (to string enum), nested Data objects (to object), and Data collections (to array of objects).
 * Applies validation attributes (Min, Max, Enum, Regex) to the schema when present.
 * Uses outputMappedName for property keys so generated schema matches API (e.g. snake_case).
 */
final class DataToJsonSchemaConverter
{
    public function __construct(
        private readonly DataConfig $dataConfig,
        private readonly JsonSchema $schema,
    ) {}

    /**
     * Build a JSON Schema ObjectType for the given Data class.
     *
     * @param  class-string  $dataClass  Data class name (must implement BaseData)
     */
    public function forData(string $dataClass): ObjectType
    {
        return $this->schema->object($this->propertiesForData($dataClass));
    }

    /**
     * Build the property map (key => Type) for the given Data class.
     * Use this when you need the raw properties array (e.g. for input schema).
     *
     * @param  class-string  $dataClass  Data class name (must implement BaseData)
     * @return array<string, Type>
     */
    public function propertiesForData(string $dataClass): array
    {
        $dataClassInstance = $this->dataConfig->getDataClass($dataClass);

        return $this->buildPropertiesForDataClass($dataClassInstance, []);
    }

    /**
     * @param  array<string>  $visited  Class names already being visited (for cycle detection)
     * @return array<string, Type>
     */
    private function buildPropertiesForDataClass(DataClass $dataClass, array $visited): array
    {
        $properties = [];

        foreach ($dataClass->properties as $dataProperty) {
            if ($dataProperty->computed || $dataProperty->hidden) {
                continue;
            }

            $key = $dataProperty->outputMappedName ?? $dataProperty->name;
            $type = $this->resolvePropertyType($dataProperty, $visited);

            $this->applyValidationAttributes($dataProperty, $type);
            $this->applyNullableAndRequired($dataProperty, $type);

            $properties[$key] = $type;
        }

        return $properties;
    }

    /**
     * @param  array<string>  $visited
     */
    private function resolvePropertyType(DataProperty $dataProperty, array $visited): Type
    {
        $propertyType = $dataProperty->type;
        $kind = $propertyType->kind;

        if ($kind === DataTypeKind::DataObject && $propertyType->dataClass !== null) {
            return $this->resolveNestedDataObject($propertyType->dataClass, $visited);
        }

        if ($kind->isDataCollectable() && $propertyType->dataClass !== null) {
            return $this->resolveDataCollection($propertyType->dataClass, $visited);
        }

        if ($kind === DataTypeKind::Array || $kind === DataTypeKind::Enumerable) {
            return $this->resolvePlainArray($propertyType);
        }

        return $this->resolveScalarOrEnum($dataProperty);
    }

    /**
     * @param  array<string>  $visited
     */
    private function resolveNestedDataObject(string $dataClass, array $visited): Type
    {
        if (in_array($dataClass, $visited, true)) {
            return $this->schema->object([]);
        }

        $visited[] = $dataClass;
        $nestedDataClass = $this->dataConfig->getDataClass($dataClass);
        $properties = $this->buildPropertiesForDataClass($nestedDataClass, $visited);

        return $this->schema->object($properties);
    }

    /**
     * @param  array<string>  $visited
     */
    private function resolveDataCollection(string $dataClass, array $visited): Type
    {
        $itemType = $this->resolveNestedDataObject($dataClass, $visited);

        return $this->schema->array()->items($itemType);
    }

    private function resolvePlainArray(DataPropertyType $propertyType): Type
    {
        $itemTypeName = $propertyType->iterableItemType ?? 'string';
        $itemType = $this->scalarTypeFromName($itemTypeName);

        return $this->schema->array()->items($itemType);
    }

    private function resolveScalarOrEnum(DataProperty $dataProperty): Type
    {
        $propertyType = $dataProperty->type;
        $innerType = $propertyType->type ?? null;

        if ($innerType instanceof NamedType) {
            $enumClass = $innerType->findAcceptedTypeForBaseType(BackedEnum::class);
            if ($enumClass !== null && is_subclass_of($enumClass, BackedEnum::class)) {
                $backed = $enumClass;
                $values = array_column($backed::cases(), 'value');
                $first = $values[0] ?? null;
                if (is_int($first)) {
                    return $this->schema->integer()->enum($enumClass);
                }

                return $this->schema->string()->enum($enumClass);
            }

            return $this->scalarTypeFromName($innerType->name);
        }

        return $this->resolveScalarFromNamedType($dataProperty);
    }

    private function resolveScalarFromNamedType(DataProperty $dataProperty): Type
    {
        $propertyType = $dataProperty->type;
        $innerType = $propertyType->type;
        if (! $innerType instanceof NamedType) {
            return $this->schema->string();
        }

        return $this->scalarTypeFromName($innerType->name);
    }

    private function scalarTypeFromName(string $name): Type
    {
        return match ($name) {
            'string' => $this->schema->string(),
            'int', 'integer' => $this->schema->integer(),
            'float', 'double' => $this->schema->number(),
            'bool', 'boolean' => $this->schema->boolean(),
            'array' => $this->schema->array(),
            default => $this->schema->string(),
        };
    }

    private function applyValidationAttributes(DataProperty $dataProperty, Type $type): void
    {
        $rules = $dataProperty->attributes->all(ValidationRule::class);

        foreach ($rules as $rule) {
            if ($rule instanceof Min) {
                $minVal = $rule->parameters()[0] ?? null;
                if ($minVal !== null && ! $minVal instanceof ExternalReference && is_int($minVal)
                    && ($type instanceof StringType
                        || $type instanceof IntegerType
                        || $type instanceof NumberType)) {
                    $type->min($minVal);
                }
            }

            if ($rule instanceof Max) {
                $maxVal = $rule->parameters()[0] ?? null;
                if ($maxVal !== null && ! $maxVal instanceof ExternalReference && is_int($maxVal)
                    && ($type instanceof StringType
                        || $type instanceof IntegerType
                        || $type instanceof NumberType)) {
                    $type->max($maxVal);
                }
            }

            if ($rule instanceof EnumRule) {
                $enumClass = $this->resolveEnumClassFromRule($rule);
                if ($enumClass !== null && is_subclass_of($enumClass, BackedEnum::class)) {
                    $type->enum($enumClass);
                }
            }

            if ($rule instanceof Regex) {
                $pattern = $rule->parameters()[0] ?? null;
                if ($pattern !== null && ! $pattern instanceof ExternalReference && is_string($pattern)
                    && $type instanceof StringType) {
                    $type->pattern($pattern);
                }
            }
        }
    }

    private function resolveEnumClassFromRule(EnumRule $rule): ?string
    {
        $ref = new ReflectionClass($rule);
        $prop = $ref->getProperty('enum');
        $enum = $prop->getValue($rule);

        if ($enum instanceof ExternalReference) {
            return null;
        }
        if (is_string($enum)) {
            return $enum;
        }
        if ($enum instanceof IlluminateEnumRule) {
            $innerRef = new ReflectionClass($enum);
            $typeProp = $innerRef->getProperty('type');
            $class = $typeProp->getValue($enum);

            return is_string($class) ? $class : null;
        }

        return null;
    }

    private function applyNullableAndRequired(DataProperty $dataProperty, Type $type): void
    {
        $propertyType = $dataProperty->type;

        if ($propertyType->isNullable ?? false) {
            $type->nullable();
        }

        $optional = $propertyType->isOptional;
        $hasDefault = $dataProperty->hasDefaultValue;

        if (! $optional && ! $hasDefault) {
            $type->required();
        }
    }
}
