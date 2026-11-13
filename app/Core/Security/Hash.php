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

namespace Papastro\Security;

/**
 * Hash — Cryptographic password hashing service supporting Argon2id and Bcrypt.
 */
class Hash
{
    /**
     * Hash the given password using Argon2id (or Bcrypt fallback).
     */
    public static function make(string $value, array $options = []): string
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        return password_hash($value, $algo, $options);
    }

    /**
     * Verify a plain value against a hashed password.
     */
    public static function check(string $value, string $hashedValue): bool
    {
        if ($hashedValue === '') {
            return false;
        }

        return password_verify($value, $hashedValue);
    }

    /**
     * Check if the given hash needs to be rehashed.
     */
    public static function needsRehash(string $hashedValue, array $options = []): bool
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        return password_needs_rehash($hashedValue, $algo, $options);
    }

    /**
     * Get information about the given hashed value.
     */
    public static function info(string $hashedValue): array
    {
        return password_get_info($hashedValue);
    }
}
