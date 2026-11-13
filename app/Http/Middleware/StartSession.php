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
use Papastro\Session\Session;

/**
 * StartSession — loads the session from the request cookie and persists it after the response.
 */
class StartSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $lifetime = (int) config('session.lifetime', 120);
        $session = new Session(storage_path('sessions'), $request->cookie(Session::COOKIE), $lifetime);
        $session->start();

        app()->instance('session', $session);
        app()->instance(Session::class, $session);

        $response = $next($request);

        $session->save();

        return $response->withCookie(
            Session::COOKIE,
            $session->id(),
            $lifetime,
            '/',
            '',
            $request->isSecure(),
            true,
            (string) config('session.same_site', 'Lax')
        );
    }
}
