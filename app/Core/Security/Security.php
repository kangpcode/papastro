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

namespace Papastro\Security;

/**
 * Security Engine — provides CSRF, XSS protection, SQL identifier safety, and secure token utilities.
 */
class Security
{
    /**
     * Timing-attack safe string comparison for CSRF tokens and signatures.
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
     * Generate a cryptographically secure UUID v4.
     */
    public static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Validate and clean a SQL column/table identifier to prevent SQL injection via dynamic column names.
     */
    public static function sanitizeSqlIdentifier(string $identifier): string
    {
        $trimmed = trim($identifier);
        if ($trimmed === '*') {
            return '*';
        }
        // Allow alphanumeric, underscore, dot (table.column), and "AS" aliasing
        if (!preg_match('/^[a-zA-Z0-9_\.\*]+(\s+as\s+[a-zA-Z0-9_]+)?$/i', $trimmed)) {
            throw new \InvalidArgumentException("Unsafe SQL identifier detected: [{$identifier}]");
        }
        return $trimmed;
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

            // Strip dangerous script/iframe/object tags and inline event attributes
            $value = preg_replace('/<(script|iframe|object|embed)\b[^>]*>(.*?)<\/\1>/is', '', $value);
            $value = preg_replace('/\bon[a-z]+\s*=\s*([\'"]?)(.*?)\1/is', '', $value);
            $value = preg_replace('/(?:javascript|vbscript|data)\s*:/is', '', $value);

            return trim($value);
        }

        return $value;
    }
}
