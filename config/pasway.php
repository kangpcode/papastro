<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v3.0.0 — Pasway Realtime Configuration
 *  @author kangpcode (Dhafa Nazula Permadi) | @license see watermark
 * ------------------------------------------------------------------
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Broadcast Driver
    |--------------------------------------------------------------------------
    | Options: "websocket", "sse", "log", "null"
    */
    'driver' => env('PASWAY_DRIVER', 'websocket'),

    /*
    |--------------------------------------------------------------------------
    | WebSocket Server Settings
    |--------------------------------------------------------------------------
    */
    'server' => [
        'host' => env('PASWAY_HOST', '0.0.0.0'),
        'port' => (int) env('PASWAY_PORT', 8080),
        'ipc_port' => (int) env('PASWAY_IPC_PORT', 8081),
        'ipc_socket' => env('PASWAY_IPC_SOCKET', storage_path('framework/pasway.sock')),
        'max_connections' => (int) env('PASWAY_MAX_CONNS', 10000),
        'ping_interval' => 30, // seconds
        'ping_timeout' => 10,  // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Credentials for HMAC Authentication
    |--------------------------------------------------------------------------
    */
    'app_key' => env('PASWAY_APP_KEY', 'papastro-realtime-key'),
    'app_secret' => env('PASWAY_APP_SECRET', env('APP_KEY', 'papastro-realtime-secret')),

    /*
    |--------------------------------------------------------------------------
    | Security & CORS
    |--------------------------------------------------------------------------
    */
    'allowed_origins' => ['*'],
    'auth_endpoint' => '/pasway/auth',
];
