<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v5.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Papastro\Http\Request;
use Papastro\Http\Response;

/**
 * HandleCors — Handles Cross-Origin Resource Sharing (CORS) headers and preflight OPTIONS requests.
 */
class HandleCors
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = (string) env('CORS_ALLOWED_ORIGINS', '*');

        $corsHeaders = [
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept',
            'Access-Control-Max-Age' => '86400',
        ];

        if ($request->isMethod('OPTIONS')) {
            return Response::make('', 204, $corsHeaders);
        }

        $response = $next($request);

        return $response->withHeaders($corsHeaders);
    }
}
