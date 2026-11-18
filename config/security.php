<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework — Security Configuration
 *  @author kangpcode (Dhafa Nazula Permadi) | @license see watermark
 * ------------------------------------------------------------------
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Framework Exposure & Technology Fingerprint
    |--------------------------------------------------------------------------
    | Controls whether Papastro identifies itself via:
    |   - HTTP Header: X-Powered-By: Papastro/{version}
    |   - HTML Meta Tag: <meta name="generator" content="Papastro v{version}" />
    |   - DOM Island Attributes: data-pstro-island, data-papastro-v
    |   - Global JavaScript Object: window.__PAPASTRO__ = { version: "{version}" }
    |
    | Enables automatic detection by tech profilers (Wappalyzer, Webappanalyzer).
    | Set to false in production if security policy requires complete framework concealment.
    */
    'expose_framework' => (bool) env('PAPASTRO_EXPOSE', true),

    /*
    |--------------------------------------------------------------------------
    | Adaptive DDoS Shield
    |--------------------------------------------------------------------------
    | Layer-7 sliding window rate limiting and automatic temporary banning.
    */
    'ddos_shield' => [
        'enabled'       => (bool) env('DDOS_SHIELD_ENABLED', true),
        'max_per_min'   => (int) env('DDOS_MAX_PER_MIN', 300),
        'ban_duration'  => (int) env('DDOS_BAN_DURATION', 900), // 15 minutes
        'whitelist'     => ['127.0.0.1', '::1'],
    ],
];
