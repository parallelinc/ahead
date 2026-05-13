<?php

declare(strict_types=1);

namespace App\Support\Scramble\Extensions;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Type\FunctionType;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\Type;

/**
 * Scramble will prefer an explicit PHP return type (e.g. `: ApiResponseData`) or a `@response` tag
 * when generating the OpenAPI response schema.
 *
 * That makes wrappers like ApiResponseData show up as a generic `ApiResponseEnvelope` where
 * `data` becomes a generic object (because ApiResponseData::$data is typed as `?Data`).
 *
 * This operation extension fixes that by overriding the generated response schema with the
 * *inferred* return type from the controller body (captured by Scramble as `inferredReturnType`)
 * when that inferred type looks like our `{success, data, ...}` envelope shape.
 */
final class ApiResponseDataInferredResponseExtension extends OperationExtension
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $methodType = $routeInfo->getMethodType();
        if (! $methodType instanceof FunctionType) {
            return;
        }

        // Prefer the body-inferred return type. This keeps working even when the controller has
        // a `@response ...` tag (which Scramble prefers for docs), because `@response` does NOT
        // necessarily populate the `inferredReturnType` attribute.
        $bodyInferredReturnType = $methodType->getReturnType();

        // Fallback: if a PHP return type annotation exists, Scramble stores the original inferred
        // type in `inferredReturnType`.
        /** @var Type|null $annotatedInferredReturnType */
        $annotatedInferredReturnType = $methodType->getAttribute('inferredReturnType');

        $typeToUse = $bodyInferredReturnType instanceof KeyedArrayType
            ? $bodyInferredReturnType
            : ($annotatedInferredReturnType instanceof KeyedArrayType ? $annotatedInferredReturnType : null);

        if (! $typeToUse || ! $this->looksLikeApiResponseEnvelopeShape($typeToUse)) {
            return;
        }

        $response = $this->openApiTransformer->toResponse($typeToUse);
        if ($response === null) {
            return;
        }

        // Replace any previously generated 200 response (whether it was inline or a $ref).
        $operation->responses = array_values(array_filter(
            $operation->responses ?? [],
            fn (Reference|Response $r): bool => $this->resolveResponseCode($r) !== 200,
        ));

        $operation->addResponse($response);
    }

    private function looksLikeApiResponseEnvelopeShape(KeyedArrayType $type): bool
    {
        $keys = collect($type->items)
            ->map(fn ($item) => $item->key)
            ->filter(fn ($k): bool => is_string($k))
            ->values()
            ->all();

        return in_array('success', $keys, true) && in_array('data', $keys, true);
    }

    private function resolveResponseCode(mixed $responseOrReference): ?int
    {
        if (is_object($responseOrReference) && method_exists($responseOrReference, 'resolve')) {
            $resolved = $responseOrReference->resolve();
            if (is_object($resolved) && isset($resolved->code)) {
                $code = $resolved->code;

                return is_int($code) ? $code : (int) (is_scalar($code) ? (string) $code : '0');
            }
        }

        if (is_object($responseOrReference) && isset($responseOrReference->code)) {
            $code = $responseOrReference->code;

            return is_int($code) ? $code : (int) (is_scalar($code) ? (string) $code : '0');
        }

        return null;
    }
}
