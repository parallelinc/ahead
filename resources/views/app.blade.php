<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        <link rel="stylesheet" href="https://rsms.me/inter/inter.css">

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
        <link rel="shortcut icon" href="/favicon.ico" />
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
        <meta name="apple-mobile-web-app-title" content="LaunchIT" />
        <link rel="manifest" href="/site.webmanifest" />

{{--        @if (app()->environment('local'))--}}
{{--            <script type="module" src="https://dev-console-v3.test:5173/@@vite-plugin-checker-runtime-entry"></script>--}}
{{--        @endif--}}
        @routes(nonce: Vite::cspNonce())
        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Ahead by Parallel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
        @env('local')
            <!-- Active Breakpoint Indicator -->
            <div class="fixed bottom-0 right-0 m-8 p-3 text-xs font-mono text-white h-6 w-6 rounded-full flex items-center justify-center bg-gray-700 sm:bg-pink-500 md:bg-orange-500 lg:bg-green-500 xl:bg-blue-500 2xl:bg-red-500">
                <div class="block  sm:hidden md:hidden lg:hidden xl:hidden">al</div>
                <div class="hidden sm:block  md:hidden lg:hidden xl:hidden">sm</div>
                <div class="hidden sm:hidden md:block  lg:hidden xl:hidden">md</div>
                <div class="hidden sm:hidden md:hidden lg:block  xl:hidden">lg</div>
                <div class="hidden sm:hidden md:hidden lg:hidden xl:block 2xl:hidden">xl</div>
                <div class="hidden sm:hidden md:hidden xl:hidden 2xl:block">2xl</div>
            </div>
            <!-- /Active Breakpoint Indicator -->
        @endenv
    </body>
</html>
