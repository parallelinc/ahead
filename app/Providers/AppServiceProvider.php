<?php

declare(strict_types=1);

namespace App\Providers;

use App\Data\FlashData;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\ScramblePro\Extensions\LaravelData\Generator\DataRequestExtension;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureVite();
        $this->registerFlashMacro();
        $this->registerScramble();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );

        URL::forceScheme('https');

        Schema::defaultStringLength(255);
    }

    private function configureVite(): void
    {
        Vite::useStyleTagAttributes(['crossorigin' => true]);
        Vite::useScriptTagAttributes(['crossorigin' => true]);
        Vite::usePreloadTagAttributes(['crossorigin' => true]);
        Vite::prefetch();
    }

    private function registerFlashMacro(): void
    {
        RedirectResponse::macro('flash', function (string $message, ?string $description = null, ?string $type = 'success', ?string $position = 'bottom-center'): object {
            $flashData = new FlashData($message, $description, $type, $position);

            Inertia::flash('toast', $flashData);

            return $this;
        });
    }

    private function registerScramble(): void
    {
        Scramble::configure()
            ->expose(false)
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                /** @var SecurityScheme $securityScheme */
                $securityScheme = SecurityScheme::http('bearer', 'JWT');
                $openApi->secure($securityScheme);
            });

        Scramble::ignoreDefaultRoutes();

        Scramble::registerApi('v1', ['api_path' => 'api/v1', 'info' => ['version' => '1.0']])
            ->expose(ui: '/docs/v1', document: '/docs/v1/openapi.json')
            ->withOperationTransformers([DataRequestExtension::class]);
    }
}
