<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 routes
|--------------------------------------------------------------------------
|
| Scramble will generate OpenAPI docs from these routes and their route actions.
| Put Scramble endpoint annotations/attributes on the controller methods (or
| invokable controllers) these routes point to. Use Laravel Data (DTOs) +
| Scramble annotations (@example, @format, etc.) on the associated Data classes
| to enrich request/response schemas.
|
*/
Route::scopeBindings()
    ->middleware(['auth:api'])
    ->group(function (): void {
        //
    });
