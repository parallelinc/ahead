<?php

declare(strict_types=1);

namespace App\Data;

use Inertia\Inertia;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class FlashData extends Data
{
    public function __construct(
        public string $message,
        public null|string|Optional $description = null,
        public null|string|Optional $type = 'success',
        public null|string|Optional $position = 'bottom-center',
    ) {}

    public static function success(string $message, ?string $description = null, ?string $position = null): void
    {
        $data = new self($message, $description, 'success', $position);

        Inertia::flash('toast', $data);
    }

    public static function error(string $message, ?string $description = null, ?string $position = null): void
    {
        $data = new self($message, $description, 'error', $position);

        Inertia::flash('toast', $data);
    }
}
