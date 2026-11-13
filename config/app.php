<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework — Application Configuration
 *  @author kangpcode (Dhafa Nazula Permadi) | @license see watermark
 * ------------------------------------------------------------------
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */
    'name' => env('APP_NAME', 'Papastro App'),

    /*
    |--------------------------------------------------------------------------
    | Application Version
    |--------------------------------------------------------------------------
    */
    'version' => '3.0.0',

    /*
    |--------------------------------------------------------------------------
    | Installation Mode
    |--------------------------------------------------------------------------
    | Chosen during `php papastro install`:
    |   modules → app/Modules only, the plugin system is disabled
    |   full    → app/Modules plus plug-and-play plugins
    */
    'install_mode' => env('PAPASTRO_MODE', 'full'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    | Options: local, staging, production
    */
    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    | When true, error details are shown in the browser.
    | NEVER enable in production!
    */
    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    */
    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    */
    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale
    |--------------------------------------------------------------------------
    */
    'locale' => env('APP_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Application Key
    |--------------------------------------------------------------------------
    | Used for encryption and signing. Generate with: papastro key:generate
    */
    'key' => env('APP_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Service Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        \App\Providers\AppServiceProvider::class,
        \App\Providers\RouteServiceProvider::class,
        \App\Providers\DatabaseServiceProvider::class,
        \App\Providers\EventServiceProvider::class,
        \App\Providers\ModuleServiceProvider::class,
        \App\Providers\PluginServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins (third-party, plug-and-play)
    |--------------------------------------------------------------------------
    | Set PLUGINS_ENABLED=false in .env to disable the entire plugin system.
    */
    'plugins' => [
        'enabled' => (bool) env('PLUGINS_ENABLED', true),
        'auto_discover' => (bool) env('PLUGINS_AUTO_DISCOVER', true),

        // Only used when auto_discover=false. Example: ['AcmeWidget']
        'explicit' => [],
        'disabled' => [],
    ],
];
