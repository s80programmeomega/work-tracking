// import { defineConfig, loadEnv } from 'vite';
// import laravel from 'laravel-vite-plugin';
// import vue from '@vitejs/plugin-vue';
// import { fileURLToPath, URL } from 'node:url';

// export default defineConfig(({ mode }) => {
//     const env = loadEnv(mode, process.cwd(), '');
    
//     return {
//         plugins: [
//             laravel({
//                 input: ['resources/css/app.css', 'resources/js/app.js'],
//                 refresh: true,
//                 // ✅ CORRECTION : Indiquer à Laravel le bon chemin des assets
//                 buildDirectory: 'build',
//             }),
//             vue({
//                 template: {
//                     transformAssetUrls: {
//                         base: null,
//                         includeAbsolute: false,
//                     },
//                 },
//             }),
//         ],
//         resolve: {
//             alias: {
//                 '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
//                 '@images': fileURLToPath(new URL('./resources/js/assets/images', import.meta.url)),
//             },
//         },
//         build: {
//             manifest: true,
//             outDir: 'public/build',
//             assetsDir: 'assets',
//             emptyOutDir: true,
//             rollupOptions: {
//                 output: {
//                     manualChunks: undefined,
//                     // ✅ Forcer le chemin des assets avec /build/
//                     assetFileNames: (assetInfo) => {
//                         return 'assets/[name]-[hash][extname]';
//                     },
//                     chunkFileNames: 'assets/[name]-[hash].js',
//                     entryFileNames: 'assets/[name]-[hash].js',
//                 },
//             },
//         },
//         // ✅ RETOUR à base: '/' pour ne pas affecter Vue Router
//         base: '/',
        
//         // ✅ AJOUT : Configuration expérimentale pour préfixer les assets
//         experimental: {
//             renderBuiltUrl(filename, { hostType }) {
//                 if (hostType === 'js') {
//                     return '/build/' + filename;
//                 }
//                 return '/build/' + filename;
//             }
//         },
        
//         server: {
//             host: true,
//         },
//         define: {
//             'process.env.NODE_ENV': JSON.stringify(mode),
//         },
//         publicDir: false,
//     };
// });




import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
            '@images': fileURLToPath(new URL('./resources/js/assets/images', import.meta.url))
        },
    },
});

