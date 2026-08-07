/*
 * Papastro Framework — Island Build Configuration
 * created by kangpcode (Dhafa Nazula Permadi)
 * Free to use & extend. This watermark must remain in source code.
 *
 * This file configures how island components are built and served.
 * Similar to astro.config.mjs
 */

/** @type {import('./papastro').PapastroConfig} */
export default {
    /*
    |--------------------------------------------------------------------------
    | Island Build Settings
    |--------------------------------------------------------------------------
    */
    islands: {
        // Directories to scan for island components
        dirs: [
            './resources/islands',
            './app/Modules/*/Islands',
        ],

        // Output directory (relative to public/)
        outDir: './public/islands',

        // Module format: 'esm' (recommended) | 'iife'
        format: 'esm',

        // Enable tree-shaking (production only)
        treeShake: true,

        // Enable island-level code splitting
        splitting: true,
    },

    /*
    |--------------------------------------------------------------------------
    | Development Server
    |--------------------------------------------------------------------------
    */
    server: {
        host: '127.0.0.1',
        port: 8000,
        hmr: true, // Hot Module Replacement for islands
    },

    /*
    |--------------------------------------------------------------------------
    | Build Optimization
    |--------------------------------------------------------------------------
    */
    build: {
        // Minify JS islands in production
        minify: true,

        // Generate source maps
        sourcemap: false,

        // CSS extraction
        extractCss: true,
    },

    /*
    |--------------------------------------------------------------------------
    | PWA Settings
    |--------------------------------------------------------------------------
    | These are merged with config/pwa.php during build
    */
    pwa: {
        enabled: true,
        registerSW: true,
    },
};
