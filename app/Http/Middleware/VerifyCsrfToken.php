<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v1.2.0
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
use Papastro\Http\Exception\TokenMismatchException;
use Papastro\Security\Security;

class VerifyCsrfToken
{
    /**
     * URIs that should be excluded from CSRF verification.
     */
    protected array $except = [
        'api/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isReading($request) || $this->inExceptArray($request) || $this->tokensMatch($request)) {
            return $next($request);
        }

        throw new TokenMismatchException('CSRF token mismatch. Page expired.');
    }

    protected function isReading(Request $request): bool
    {
        return in_array($request->method(), ['GET', 'HEAD', 'OPTIONS']);
    }

    protected function inExceptArray(Request $request): bool
    {
        $uri = ltrim($request->uri(), '/');

        foreach ($this->except as $except) {
            if ($except === $uri) {
                return true;
            }

            if (str_ends_with($except, '*') && str_starts_with($uri, rtrim($except, '*'))) {
                return true;
            }
        }

        return false;
    }

    protected function tokensMatch(Request $request): bool
    {
        $token     = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');
        $sessionToken = session('_token');

        if (!$token || !$sessionToken) {
            return false;
        }

        return Security::compareTokens($sessionToken, $token);
    }
}
