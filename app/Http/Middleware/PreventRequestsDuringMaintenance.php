<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v3.0.0
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

class PreventRequestsDuringMaintenance
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $downFile = function_exists('storage_path')
            ? storage_path('framework/down')
            : dirname(__DIR__, 2) . '/storage/framework/down';

        if (!file_exists($downFile)) {
            return $next($request);
        }

        $data = json_decode((string) @file_get_contents($downFile), true) ?: [];
        $secret = $data['secret'] ?? null;
        $message = $data['message'] ?? 'Service Unavailable. We are currently undergoing scheduled maintenance.';
        $retry = (int) ($data['retry'] ?? 60);

        // Check secret URL bypass: /{secret}
        $uri = trim($request->getPath(), '/');
        if ($secret && $uri === $secret) {
            $cookieHash = hash('sha256', (string) $secret);
            return Response::redirect('/')
                ->withCookie('papastro_maintenance_bypass', $cookieHash, 86400, '/', null, false, true);
        }

        // Check bypass cookie
        if ($secret) {
            $bypassCookie = $request->cookie('papastro_maintenance_bypass');
            if ($bypassCookie === hash('sha256', (string) $secret)) {
                return $next($request);
            }

            // Check bypass header
            if ($request->header('X-Maintenance-Secret') === $secret) {
                return $next($request);
            }
        }

        if ($request->wantsJson() || $request->isJson()) {
            return Response::json([
                'message' => $message,
                'retry_after' => $retry,
            ], 503, ['Retry-After' => (string) $retry]);
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode — Papastro</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { max-width: 500px; text-align: center; padding: 2.5rem; background: #1e293b; border-radius: 1rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); border: 1px solid #334155; }
        h1 { font-size: 1.875rem; margin-bottom: 0.75rem; color: #38bdf8; }
        p { color: #94a3b8; line-height: 1.6; }
        .badge { display: inline-block; background: #0369a1; color: #e0f2fe; padding: 0.35rem 0.75rem; border-radius: 9999px; font-size: 0.85rem; font-weight: 600; margin-top: 1.5rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>⚙️ Under Maintenance</h1>
        <p>{$message}</p>
        <div class="badge">Papastro Framework 3.0</div>
    </div>
</body>
</html>
HTML;

        return Response::html($html, 503, ['Retry-After' => (string) $retry]);
    }
}
