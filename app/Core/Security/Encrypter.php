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

use RuntimeException;

/**
 * Encrypter — Authenticated AES-256-CBC encryption with HMAC-SHA256 verification.
 */
class Encrypter
{
    protected string $key;
    protected string $cipher = 'AES-256-CBC';

    public function __construct(?string $key = null)
    {
        $rawKey = $key ?? (string) config('app.key', env('APP_KEY', ''));

        if (str_starts_with($rawKey, 'base64:')) {
            $rawKey = base64_decode(substr($rawKey, 7), true) ?: '';
        }

        if (strlen($rawKey) < 16) {
            // Derive a 32-byte key deterministically if a short passphrase or fallback is used
            $rawKey = hash('sha256', $rawKey ?: 'papastro-default-key', true);
        } elseif (strlen($rawKey) !== 32) {
            $rawKey = substr(hash('sha256', $rawKey, true), 0, 32);
        }

        $this->key = $rawKey;
    }

    /**
     * Encrypt any serializable value.
     */
    public function encrypt(mixed $value, bool $serialize = true): string
    {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = random_bytes($ivLength);

        $plaintext = $serialize ? serialize($value) : (string) $value;
        $ciphertext = openssl_encrypt($plaintext, $this->cipher, $this->key, 0, $iv);

        if ($ciphertext === false) {
            throw new RuntimeException('Could not encrypt the data.');
        }

        $ivBase64 = base64_encode($iv);
        $mac = $this->hash($ivBase64, $ciphertext);

        $json = json_encode([
            'iv' => $ivBase64,
            'value' => $ciphertext,
            'mac' => $mac,
        ], JSON_UNESCAPED_SLASHES);

        return base64_encode((string) $json);
    }

    /**
     * Decrypt an encrypted payload.
     */
    public function decrypt(string $payload, bool $unserialize = true): mixed
    {
        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            throw new RuntimeException('The payload is invalid.');
        }

        $data = json_decode($decoded, true);
        if (!is_array($data) || !isset($data['iv'], $data['value'], $data['mac'])) {
            throw new RuntimeException('The payload structure is invalid.');
        }

        $expectedMac = $this->hash((string) $data['iv'], (string) $data['value']);
        if (!hash_equals($expectedMac, (string) $data['mac'])) {
            throw new RuntimeException('The MAC is invalid.');
        }

        $iv = base64_decode((string) $data['iv'], true);
        if ($iv === false) {
            throw new RuntimeException('Could not decode IV.');
        }

        $decrypted = openssl_decrypt((string) $data['value'], $this->cipher, $this->key, 0, $iv);
        if ($decrypted === false) {
            throw new RuntimeException('Could not decrypt the data.');
        }

        return $unserialize ? unserialize($decrypted) : $decrypted;
    }

    /**
     * Compute the HMAC-SHA256 signature.
     */
    protected function hash(string $ivBase64, string $value): string
    {
        return hash_hmac('sha256', $ivBase64 . $value, $this->key);
    }
}
