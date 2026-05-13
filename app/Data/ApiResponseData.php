<?php

declare(strict_types=1);

namespace App\Data;

use Dedoc\Scramble\Attributes\SchemaName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\LaravelData\Data;

#[SchemaName('ApiResponseEnvelope')]
final class ApiResponseData extends Data
{
    /**
     * @param  array<string, array<int, string>>|null  $errors  For validation/errors, e.g., ['field' => ['msg1', 'msg2']]
     */
    public function __construct(
        public bool $success = true,
        public mixed $data = null,  // Concrete type is inferred per endpoint by Scramble extensions
        public ?string $message = null,
        public ?array $errors = null,
    ) {}

    /**
     * Ensure we keep readable Unicode in JSON responses (instead of \uXXXX escaping),
     * while preserving the default Laravel/Symfony JSON encoding safety flags.
     */
    public function toResponse($request): JsonResponse
    {
        $response = parent::toResponse($request);

        if ($response instanceof JsonResponse) {
            $response->setEncodingOptions(
                $response->getEncodingOptions() | JSON_UNESCAPED_UNICODE
            );
        }

        return $response instanceof JsonResponse ? $response : new JsonResponse($this->toArray(), 200);
    }

    /**
     * Keep response status stable (we used to always return 200 via `response()->json(..., 200)`).
     */
    protected function calculateResponseStatus(Request $request): int
    {
        return Response::HTTP_OK;
    }
}
