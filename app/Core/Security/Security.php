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

namespace Papastro\Security;

/**
 * Security Engine — provides CSRF, XSS protection, and secure token utilities.
 */
class Security
{
    /**
     * Timing-attack safe string comparison for CSRF tokens.
     */
    public static function compareTokens(string $known, string $user): bool
    {
        return hash_equals($known, $user);
    }

    /**
     * Generate cryptographically secure random token string.
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Recursively sanitize input against XSS vulnerabilities.
     */
    public static function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $val) {
                $sanitized[static::sanitize($key)] = static::sanitize($val);
            }
            return $sanitized;
        }

        if (is_string($value)) {
            // Strip null bytes
            $value = str_replace(chr(0), '', $value);

            // Strip dangerous script tags and inline event attributes
            $value = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $value);
            $value = preg_replace('/on[a-z]+\s*=\s*([\'"]?)(.*?)\1/is', '', $value);
            $value = preg_replace('/javascript\s*:/is', '', $value);

            return trim($value);
        }

        return $value;
    }
}
