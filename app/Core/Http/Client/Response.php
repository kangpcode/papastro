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

namespace Papastro\Http\Client;

use JsonSerializable;

/**
 * PasFetch HTTP Client Response.
 */
class Response implements JsonSerializable
{
    public function __construct(
        protected int $status,
        protected string $body,
        protected array $headers = []
    ) {}

    public function status(): int
    {
        return $this->status;
    }

    public function ok(): bool
    {
        return $this->status === 200;
    }

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function redirect(): bool
    {
        return $this->status >= 300 && $this->status < 400;
    }

    public function failed(): bool
    {
        return $this->serverError() || $this->clientError();
    }

    public function clientError(): bool
    {
        return $this->status >= 400 && $this->status < 500;
    }

    public function serverError(): bool
    {
        return $this->status >= 500;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        $decoded = json_decode($this->body, true);

        if (!is_array($decoded)) {
            return $default;
        }

        if ($key === null) {
            return $decoded;
        }

        $segments = explode('.', $key);
        $curr = $decoded;

        foreach ($segments as $segment) {
            if (is_array($curr) && array_key_exists($segment, $curr)) {
                $curr = $curr[$segment];
            } else {
                return $default;
            }
        }

        return $curr;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $key): ?string
    {
        $lookup = strtolower($key);
        foreach ($this->headers as $k => $v) {
            if (strtolower($k) === $lookup) {
                return is_array($v) ? implode(', ', $v) : (string) $v;
            }
        }
        return null;
    }

    public function __toString(): string
    {
        return $this->body;
    }

    public function jsonSerialize(): mixed
    {
        return $this->json();
    }
}

