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

namespace Papastro\Session;

/**
 * Session — SAPI-independent, file-backed session store.
 *
 * Unlike native PHP sessions this works identically under PHP-FPM, the built-in
 * server and the long-running Papastro Runtime (where there is no SAPI cookie handling).
 * The session id travels in the "papastro_session" cookie managed by StartSession middleware.
 */
class Session
{
    public const COOKIE = 'papastro_session';

    protected string $id;
    protected array $data = [];
    protected bool $started = false;

    public function __construct(
        protected string $path,
        ?string $id = null,
        protected int $lifetimeMinutes = 120
    ) {
        $this->id = ($id !== null && self::isValidId($id)) ? $id : self::generateId();
    }

    public static function isValidId(string $id): bool
    {
        return (bool) preg_match('/^[a-f0-9]{40}$/', $id);
    }

    public static function generateId(): string
    {
        return bin2hex(random_bytes(20));
    }

    public function start(): static
    {
        if ($this->started) {
            return $this;
        }

        $file = $this->file();
        if (is_file($file) && (filemtime($file) + $this->lifetimeMinutes * 60) >= time()) {
            $raw = @file_get_contents($file);
            $data = $raw === false ? null : @unserialize($raw, ['allowed_classes' => false]);
            $this->data = is_array($data) ? $data : [];
        } else {
            // Unknown / expired id supplied by client: never trust it (session fixation) — issue a fresh one.
            $this->id = self::generateId();
            $this->data = [];
        }

        $this->started = true;
        return $this;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function put(string|array $key, mixed $value = null): static
    {
        foreach (is_array($key) ? $key : [$key => $value] as $k => $v) {
            $this->data[$k] = $v;
        }
        return $this;
    }

    public function forget(string $key): static
    {
        unset($this->data[$key]);
        return $this;
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);
        return $value;
    }

    public function flash(string $key, mixed $value): static
    {
        $this->data['_flash'][$key] = $value;
        return $this;
    }

    public function pullFlash(string $key, mixed $default = null): mixed
    {
        $value = $this->data['_flash'][$key] ?? $default;
        unset($this->data['_flash'][$key]);
        return $value;
    }

    public function pullAllFlash(): array
    {
        $all = $this->data['_flash'] ?? [];
        $this->data['_flash'] = [];
        return $all;
    }

    public function token(): string
    {
        if (empty($this->data['_token'])) {
            $this->data['_token'] = bin2hex(random_bytes(32));
        }
        return $this->data['_token'];
    }

    /**
     * Issue a new id keeping the data. Call after login/privilege change (prevents fixation).
     */
    public function regenerate(bool $destroyOld = true): static
    {
        if ($destroyOld) {
            @unlink($this->file());
        }
        $this->id = self::generateId();
        return $this;
    }

    public function flush(): static
    {
        $this->data = [];
        return $this;
    }

    public function invalidate(): static
    {
        $this->flush();
        return $this->regenerate(true);
    }

    public function save(): void
    {
        if (!$this->started) {
            return;
        }

        if (!is_dir($this->path)) {
            @mkdir($this->path, 0700, true);
        }

        $file = $this->file();
        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, serialize($this->data), LOCK_EX) !== false) {
            @chmod($tmp, 0600);
            @rename($tmp, $file);
        }

        // Probabilistic garbage collection (1 in 100 saves).
        if (random_int(1, 100) === 1) {
            $this->gc();
        }
    }

    public function gc(): int
    {
        $removed = 0;
        $cutoff = time() - $this->lifetimeMinutes * 60;
        foreach (glob($this->path . '/*') ?: [] as $f) {
            if (is_file($f) && filemtime($f) < $cutoff) {
                @unlink($f);
                $removed++;
            }
        }
        return $removed;
    }

    protected function file(): string
    {
        return $this->path . DIRECTORY_SEPARATOR . $this->id;
    }
}
