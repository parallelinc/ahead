<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

final class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    private const MAX_TAG_LENGTH = 250;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->hideSensitiveRequestDetails();

        Telescope::filter(function (IncomingEntry $entry) {
            try {
                $content = $entry->content;

                if ($entry->type === 'job') {
                    $name = $content['name'] ?? null;
                    if (is_string($name) && Str::contains($name, ['HealthQueueJob'], true)) {
                        return false;
                    }
                }

                if ($entry->type === 'request') {
                    $uri = $content['uri'] ?? null;
                    if (is_string($uri) && Str::startsWith($uri, ['/favicon', '/apple-touch-icon'])) {
                        return false;
                    }
                }

                if ($entry->type === 'event') {
                    $name = $content['name'] ?? null;
                    if (is_string($name) && Str::contains($name, ['Spatie\Health\Events'], true)) {
                        return false;
                    }
                }

                if ($entry->type === 'log') {
                    $message = $content['message'] ?? null;
                    if (is_string($message) && Str::contains($message, ['Creation of dynamic property'], true)) {
                        return false;
                    }
                }
            } catch (Exception $e) {
                Log::error('Telescope filtering error!', ['message' => $e->getMessage(), 'content' => $entry->content, 'error' => $e]);

                return false;
            }

            return true;
        });

        Telescope::tag(function (IncomingEntry $entry) {
            try {
                $content = $entry->content;

                $tag = fn (string $value): string => Str::limit($value, self::MAX_TAG_LENGTH);

                $tags = match ($entry->type) {
                    'service' => ['dev-console'],
                    'log' => ['level:'.(is_scalar($content['level'] ?? null) ? (string) ($content['level']) : 'unknown')],
                    'request' => [
                        'status:'.(is_scalar($content['response_status'] ?? null) ? (string) ($content['response_status']) : 'N/A'),
                        'method:'.(is_string($content['method'] ?? null) ? $content['method'] : ''),
                        'url:'.Str::limit(is_string($content['uri'] ?? null) ? $content['uri'] : '', 240),
                        'ip:'.(is_scalar($content['ip_address'] ?? null) ? (string) ($content['ip_address']) : ''),
                        'hostname:'.(is_string($content['hostname'] ?? null) ? $content['hostname'] : ''),
                    ],
                    'client_request' => [
                        'status:'.(is_scalar($content['response_status'] ?? null) ? (string) ($content['response_status']) : 'N/A'),
                        'method:'.(is_string($content['method'] ?? null) ? $content['method'] : ''),
                        'url:'.Str::limit(is_string($content['uri'] ?? null) ? $content['uri'] : '', 240),
                    ],
                    'query' => ['slow:'.(is_scalar($content['slow'] ?? null) ? (string) ($content['slow']) : '')],
                    'job' => [
                        'status:'.(is_string($content['status'] ?? null) ? $content['status'] : ''),
                        'connection:'.(is_string($content['connection'] ?? null) ? $content['connection'] : ''),
                        'type:'.(is_string($content['name'] ?? null) ? $content['name'] : ''),
                    ],
                    'cache' => ['status:'.(is_string($content['type'] ?? null) ? $content['type'] : '')],
                    'mail' => [
                        'type:'.(is_string($content['mailable'] ?? null) ? $content['mailable'] : ''),
                        'to:'.(is_array($content['to'] ?? null) ? json_encode($content['to']) : ''),
                    ],
                    'notification' => [
                        'type:'.(is_string($content['notification'] ?? null) ? $content['notification'] : ''),
                        'channel:'.(is_string($content['channel'] ?? null) ? $content['channel'] : ''),
                    ],
                    'model' => [
                        'action:'.(is_string($content['action'] ?? null) ? $content['action'] : ''),
                        'model:'.(is_string($content['model'] ?? null) ? $content['model'] : ''),
                    ],
                    default => [],
                };

                return array_map($tag, $tags);
            } catch (Exception $e) {
                Log::error('Telescope tagging error!', ['message' => $e->getMessage(), 'content' => $entry->content, 'error' => $e]);

                return [];
            }
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if (! app()->environment('local')) {
            Telescope::hideRequestParameters(['_token']);

            Telescope::hideResponseParameters(['_token']);

            Telescope::hideRequestHeaders(['cookie', 'x-csrf-token', 'x-xsrf-token']);
        }

        Telescope::$hiddenRequestParameters = array_unique(Telescope::$hiddenRequestParameters);
        Telescope::$hiddenResponseParameters = array_unique(Telescope::$hiddenResponseParameters);
        Telescope::$hiddenRequestHeaders = array_unique(Telescope::$hiddenRequestHeaders);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function (User $user) {
            if (app()->isLocal()) {
                return true;
            }

            return $user->isSuperAdmin();
        });
    }
}
