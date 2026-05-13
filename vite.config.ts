import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny, local, type LocalVariantDefinition } from 'laravel-vite-plugin/fonts';
import path from 'path';
import { defineConfig } from 'vite';
import checker from 'vite-plugin-checker';

const berkeleyMonoVariants = [
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-Thin.woff2', weight: 100 },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-Thin-Oblique.woff2', weight: 100, style: 'italic' },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-ExtraLight.woff2', weight: 200 },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-ExtraLight-Oblique.woff2', weight: 200, style: 'italic' },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-Light.woff2', weight: 300 },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-Light-Oblique.woff2', weight: 300, style: 'italic' },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-SemiLight.woff2', weight: 350 },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-SemiLight-Oblique.woff2', weight: 350, style: 'italic' },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-Regular.woff2', weight: 400 },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-Oblique.woff2', weight: 400, style: 'italic' },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-Medium.woff2', weight: 500 },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-Medium-Oblique.woff2', weight: 500, style: 'italic' },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-SemiBold.woff2', weight: 600 },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-SemiBold-Oblique.woff2', weight: 600, style: 'italic' },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-Bold.woff2', weight: 700 },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-Bold-Oblique.woff2', weight: 700, style: 'italic' },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-ExtraBold.woff2', weight: 800 },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-ExtraBold-Oblique.woff2', weight: 800, style: 'italic' },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-Black.woff2', weight: 900 },
    { src: './resources/fonts/BerkeleyMono/BerkeleyMono-Black-Oblique.woff2', weight: 900, style: 'italic' },
] satisfies LocalVariantDefinition[];

export default defineConfig({
    plugins: [
        checker({
            vueTsc: true,
            overlay: true,
        }),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                local('BerkeleyMono', {
                    variants: berkeleyMonoVariants,
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            '@images': '/resources/images',
            '@css': '/resources/css',
        },
    },
});
