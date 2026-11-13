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
 * Authenticate — requires a logged-in user (session user_id).
 */
class Authenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (empty(session()->get('user_id'))) {
            if ($request->expectsJson()) {
                return Response::json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
            }
            return Response::redirect('/login');
        }

        return $next($request);
    }
}
