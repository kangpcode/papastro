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

namespace Papastro\Router;

/**
 * Utility for merging route group attributes.
 */
class RouteGroup
{
    /**
     * Merge route group attributes: prefixes concatenate, names concatenate,
     * middleware accumulate (de-duplicated), everything else is overridden by $new.
     */
    public static function merge(array $new, array $old): array
    {
        $merged = array_merge($old, $new);

        $prefix = trim(trim((string) ($old['prefix'] ?? ''), '/') . '/' . trim((string) ($new['prefix'] ?? ''), '/'), '/');
        if ($prefix !== '') {
            $merged['prefix'] = $prefix;
        } else {
            unset($merged['prefix']);
        }

        $middleware = array_merge((array) ($old['middleware'] ?? []), (array) ($new['middleware'] ?? []));
        $merged['middleware'] = array_values(array_unique($middleware));

        if (isset($old['as']) || isset($new['as'])) {
            $merged['as'] = ($old['as'] ?? '') . ($new['as'] ?? '');
        }

        return $merged;
    }
}
