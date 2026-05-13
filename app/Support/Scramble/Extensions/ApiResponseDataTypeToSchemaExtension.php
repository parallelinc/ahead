<?php

declare(strict_types=1);

namespace App\Support\Scramble\Extensions;

use App\Data\ApiResponseData;
use Dedoc\Scramble\Infer\Extensions\Event\StaticMethodCallEvent;
use Dedoc\Scramble\Infer\Extensions\StaticMethodReturnTypeExtension;
use Dedoc\Scramble\Support\Type\ArrayItemType_;
use Dedoc\Scramble\Support\Type\ArrayType;
use Dedoc\Scramble\Support\Type\BooleanType;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\NullType;
use Dedoc\Scramble\Support\Type\StringType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\Union;
use Dedoc\Scramble\Support\Type\UnknownType;

/**
 * Helps Scramble infer the concrete schema for ApiResponseData::$data based on the
 * DTO passed into the `data` key when calling ApiResponseData::from([...]).
 *
 * This intentionally returns a keyed-array/object shape type (instead of BaseData),
 * because ApiResponseData::$data is declared as `?Data` and Scramble cannot infer
 * the concrete inner DTO from that declaration alone.
 */
final class ApiResponseDataTypeToSchemaExtension implements StaticMethodReturnTypeExtension
{
    public function shouldHandle(string $name): bool
    {
        return is_a($name, ApiResponseData::class, true);
    }

    public function getStaticMethodReturnType(StaticMethodCallEvent $event): ?Type
    {
        // We only specialize ApiResponseData::from(...) calls.
        if ($event->name !== 'from') {
            return null;
        }

        // Laravel Data uses `from($payloads)` (name in Scramble Pro inference); we use position 0 anyway.
        $payloadType = $event->getArg('payloads', 0, new ArrayType);

        $dataType = $this->inferDataTypeFromPayload($payloadType);

        return new KeyedArrayType([
            new ArrayItemType_('success', new BooleanType),
            new ArrayItemType_('data', $this->nullable($dataType)),
            new ArrayItemType_('message', $this->nullable(new StringType), isOptional: true),
            new ArrayItemType_('errors', $this->nullable(new ArrayType), isOptional: true),
        ]);
    }

    private function inferDataTypeFromPayload(Type $payloadType): Type
    {
        if (! $payloadType instanceof KeyedArrayType) {
            return new UnknownType('ApiResponseData::from payload is not a keyed array; cannot infer `data` type.');
        }

        return $payloadType->getItemValueTypeByKey(
            'data',
            new UnknownType('ApiResponseData::from payload has no `data` key; cannot infer DTO type.'),
        );
    }

    private function nullable(Type $type): Type
    {
        if ($type instanceof NullType) {
            return $type;
        }

        if ($type instanceof Union && collect($type->types)->contains(fn (Type $t) => $t instanceof NullType)) {
            return $type;
        }

        return Union::wrap([$type, new NullType]);
    }
}
