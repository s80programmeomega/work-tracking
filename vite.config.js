import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        resolve: {
            alias: {
                '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
                '@images': fileURLToPath(new URL('./resources/js/assets/images', import.meta.url)),
            },
        },
        build: {
            manifest: true,
            outDir: 'public/build',
            assetsDir: 'assets',
            emptyOutDir: true,
            rollupOptions: {
                output: {
                    manualChunks: undefined,
                },
            },
        },
        base: '/',
        server: {
            host: true,
        },
        define: {
            'process.env.NODE_ENV': JSON.stringify(mode),
        },
        // ❌ SUPPRIMEZ cette ligne qui cause le problème :
        // publicDir: 'public',
        
        // ✅ Utilisez plutôt ceci (ou laissez par défaut) :
        publicDir: false, // Désactive la copie automatique du dossier public
    };
});





// import { defineConfig } from 'vite';
// import laravel from 'laravel-vite-plugin';
// import vue from '@vitejs/plugin-vue';
// import { fileURLToPath, URL } from 'node:url';

// export default defineConfig({
//     plugins: [
//         laravel({
//             input: ['resources/css/app.css', 'resources/js/app.js'],
//             refresh: true,
//         }),
//         vue(),
//     ],
//     resolve: {
//         alias: {
//             '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
//             '@images': fileURLToPath(new URL('./resources/js/assets/images', import.meta.url))
//         },
//     },
// });

