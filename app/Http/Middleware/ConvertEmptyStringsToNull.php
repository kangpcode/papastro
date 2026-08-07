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

class ConvertEmptyStringsToNull
{
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        $cleaned = $this->cleanArray($input);
        $request->replace($cleaned);

        return $next($request);
    }

    protected function cleanArray(array $input): array
    {
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $input[$key] = $this->cleanArray($value);
            } elseif (is_string($value) && $value === '') {
                $input[$key] = null;
            }
        }

        return $input;
    }
}
