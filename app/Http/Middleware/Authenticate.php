<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace App\Http\Middleware;

use Papastro\Http\Request;
use Papastro\Http\Response;

/**
 * Example authentication middleware stub.
 */
class Authenticate
{
    public function handle(Request $request, callable $next): Response
    {
        // TODO: Implement auth check
        // if (!session('user_id')) {
        //     return Response::redirect('/login');
        // }

        return $next($request);
    }
}
