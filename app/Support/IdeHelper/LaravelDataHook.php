<?php

declare(strict_types=1);

namespace App\Support\IdeHelper;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Barryvdh\LaravelIdeHelper\Contracts\ModelHookInterface;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

final class LaravelDataHook implements ModelHookInterface
{
    public function run(ModelsCommand $command, Model $model): void
    {
        $casts = collect($model->getCasts())
            ->filter(fn (string $value): bool => is_a($value, Data::class, allow_string: true) || Str::startsWith($value, 'Spatie\LaravelData\DataCollection'))
            ->map(function (string $value) {
                $value = Str::replaceStart('Spatie\LaravelData\DataCollection', 'Illuminate\Support\Collection', $value);

                $parts = explode(':', $value);

                if (count($parts) === 2) {
                    $class = Str::start($parts[0], '\\');
                    $generic = Str::start($parts[1], '\\');
                    $value = "$class<int, $generic>";
                }

                return $value;
            });

        if ($casts->isEmpty()) {
            return;
        }

        // @phpstan-ignore-next-line
        [$properties, $nullableColumns] = Closure::fromCallable(fn () => [$this->properties, $this->nullableColumns])->call($command);

        $casts->each(function ($cast, $field) use ($command, $properties, $nullableColumns) {
            $config = [
                ...$properties[$field],
                'type' => Str::start($cast, '\\'),
                'nullable' => $nullableColumns[$field] ?? false,
            ];

            $command->setProperty($field, ...$config);
        });
    }
}
