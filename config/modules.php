<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework — Modules Configuration
 *  @author kangpcode (Dhafa Nazula Permadi) | @license see watermark
 * ------------------------------------------------------------------
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Modules Path
    |--------------------------------------------------------------------------
    | The directory where all app modules are stored.
    */
    'path' => app_path('Modules'),

    /*
    |--------------------------------------------------------------------------
    | Auto Discover Modules
    |--------------------------------------------------------------------------
    | When true, all directories in the modules path are automatically
    | discovered and registered if they have a valid module.json file.
    */
    'auto_discover' => true,

    /*
    |--------------------------------------------------------------------------
    | Explicitly Enabled Modules
    |--------------------------------------------------------------------------
    | Only used when auto_discover is false. List module names to enable.
    */
    'enabled' => [
        'User',
        'Blog',
    ],

    /*
    |--------------------------------------------------------------------------
    | Disabled Modules
    |--------------------------------------------------------------------------
    | Modules listed here are always disabled, even with auto_discover.
    */
    'disabled' => [],
];
