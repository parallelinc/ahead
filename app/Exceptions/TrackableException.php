<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Exception that carries a typed error code for CloudWatch, logs, and consistent API responses.
 *
 * Use ExceptionType to define machine-readable codes, user-facing messages, doc URLs, and resolution hints.
 * Log messages include the type so you can query e.g. CloudWatch by code.
 */
final class TrackableException extends Exception
{
    public function __construct(
        private readonly ExceptionType $type,
        ?string $messageOverride = null,
    ) {
        parent::__construct($messageOverride ?? $type->message() ?? 'An error occurred.');
    }

    /**
     * Log the exception (with type code for CloudWatch) and throw it.
     *
     * @throws TrackableException
     */
    public static function throw(ExceptionType $type, ?string $messageOverride = null): never
    {
        $message = $messageOverride ?? $type->message() ?? 'An error occurred.';

        Log::warning("[ExceptionType:{$type->value}] {$message}", [
            'exception_type' => $type->value,
            'exception_type_name' => $type->name,
            'message' => $message,
            'user_id' => auth()->id(),
            'team_id' => request()->route()?->parameter('team'),
            'request_id' => request()->header('request-id'),
            'doc_url' => $type->docUrl(),
            'resolution' => $type->resolution(),
        ]);

        throw new self($type, $messageOverride);
    }

    public function type(): ExceptionType
    {
        return $this->type;
    }
}
