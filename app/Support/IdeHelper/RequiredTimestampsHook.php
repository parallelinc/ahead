<?php

declare(strict_types=1);

namespace App\Support\IdeHelper;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Barryvdh\LaravelIdeHelper\Contracts\ModelHookInterface;
use Illuminate\Database\Eloquent\Model;

final class RequiredTimestampsHook implements ModelHookInterface
{
    public function run(ModelsCommand $command, Model $model): void
    {
        $command->setProperty('created_at', "\Carbon\Carbon", true, true);
        $command->setProperty('updated_at', "\Carbon\Carbon", true, true);
    }
}
