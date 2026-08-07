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
     * Merge route group attributes into a new attribute array.
     */
    public static function merge(array $new, array $old): array
    {
        $new = self::formatAs($new, $old);

        return array_merge_recursive(
            self::formatAttributes($old),
            self::formatAttributes($new)
        );
    }

    /**
     * Format the 'as' (name prefix) attribute.
     */
    protected static function formatAs(array $new, array $old): array
    {
        if (isset($old['as'])) {
            $new['as'] = $old['as'] . ($new['as'] ?? '');
        }

        return $new;
    }

    /**
     * Format the attributes for merging.
     */
    protected static function formatAttributes(array $attributes): array
    {
        $attributes['middleware'] = (array) ($attributes['middleware'] ?? []);

        if (isset($attributes['prefix'])) {
            $attributes['prefix'] = trim($attributes['prefix'], '/');
        }

        return $attributes;
    }
}
