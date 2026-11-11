# Changelog — Papastro Framework

All notable changes to the Papastro Framework are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Author: **kangpcode (Dhafa Nazula Permadi)**
License: Free to use & modify. Watermark must remain in source code.

---

## [3.0.0] - 2026-11-11 — *High-Concurrency Runtime, Pastroutine & Pasway Realtime*

### Added

#### 1. Pastroutine (Go-style Concurrency with Laravel-style Simplicity)
- **Cooperative Scheduler & Coroutine Engine** (`Papastro\Pastroutine\Pastroutine`, `Scheduler`):
  - Lightweight coroutines that run cooperatively without threads.
  - Global helper `pastroutine(callable $task, ...$args)` — non-colliding with `ext-swoole`'s native `go()` function.
- **Go-style CSP Channels** (`Papastro\Pastroutine\Channel`):
  - Synchronous unbuffered channels and asynchronous buffered channels.
  - Methods: `send($value)`, `recv()`, `trySend()`, `tryRecv()`, `close()`.
  - Non-blocking polling and deadlock detection.
- **Synchronization Primitives**:
  - `WaitGroup`: `add($delta)`, `done()`, and `wait($timeout)` for parallel barrier synchronization.
  - `Mutex`: Mutual exclusion lock with `lock()`, `unlock()`, and `tryLock()`.
- **High-level Parallel Utilities** (`Papastro\Pastroutine\Parallel`):
  - `Parallel::run(array $tasks, ?float $timeout)`: Run associative array of closures concurrently and collect results.
  - `Parallel::map(array $items, callable $callback, ?float $timeout)`: Parallel mapping over collections.

#### 2. Octane-style High-Concurrency Runtime Server (`Papastro\Runtime`)
- **Pre-forking Multi-Worker Native Server** (`NativeServer`):
  - Keeps the bootstrapped framework in memory across millions of requests.
  - Uses Linux kernel `SO_REUSEPORT` socket binding for kernel-level connection balancing.
  - Master process monitors worker PIDs (`pcntl_waitpid`) and automatically spawns replacement workers upon exit.
  - Worker limits: auto-recycles workers after `--max-requests` (default: 10,000) or `--max-memory` to completely eradicate slow user-code memory leaks.
- **Swoole Coroutine Server Driver** (`SwooleServer`):
  - Native Swoole HTTP Server adapter capable of 100,000+ requests/sec with full coroutine support.
- **State Sandbox & Isolation Engine** (`StateResetter`):
  - Flushes container scoped instances between requests.
  - Automatically rolls back uncommitted database transactions.
  - Clears debugger query logs and snapshots.
  - Resets request and session instances in the IoC container.
  - Periodically triggers garbage collection cycles.
- **Hot Code Reloading** (`FileWatcher`):
  - Watches `app/`, `config/`, `routes/`, and `.env` for file changes.
  - Automatically signals workers (`SIGUSR1` / `SIGTERM`) for zero-downtime hot reloading during development.
- **Runtime CLI Commands**:
  - `php papastro runtime:start` (and `octane:start`): starts the server with options `--host`, `--port`, `--workers`, `--driver`, `--watch`.
  - `php papastro runtime:stop` (and `octane:stop`): stops server via PID.
  - `php papastro runtime:reload` (and `octane:reload`): gracefully reloads workers.
  - `php papastro runtime:status` (and `octane:status`): displays server status and master PID.

#### 3. Pasway Realtime Broadcasting (`Papastro\Pasway`)
- **RFC 6455 WebSocket Server** (`Papastro\Pasway\Server`):
  - High-performance, non-blocking WebSocket daemon with event-driven `stream_select` loop.
  - Low-latency local IPC socket/port (8081) for instantaneous push broadcasts from PHP HTTP requests.
  - Full support for public, private, and presence channels.
  - Pusher / Laravel Echo wire protocol compatibility.
- **HMAC Channel Authentication** (`Pasway::auth`, `Pasway::verifyAuth`):
  - Secure HMAC-SHA256 signature generation and verification for private and presence channels.
  - Automatic presence member joined (`pusher_internal:member_added`) and left (`pusher_internal:member_removed`) broadcast events.
- **Server-Sent Events (SSE) Fallback**:
  - `GET /pasway/sse?channels=...`: HTTP streaming fallback for environments where WebSockets are firewalled.
  - Event buffer with automatic sliding window retention.
- **Frontend Client SDK** (`public/pasway.js`):
  - Standalone, zero-dependency client library.
  - Smooth API: `pasway.channel(name).listen(...)`, `pasway.private(name).listen(...)`, `pasway.presence(name).here(...).joining(...).leaving(...)`.
  - Exponential backoff reconnection, automatic channel re-subscription, and client-side whisper event dispatching.
- **Global Helper**:
  - `broadcast(string|array $channels, string $event, array $data = [])` available throughout the application.
- **CLI Command**:
  - `php papastro pasway:start`: starts the realtime WebSocket server.

#### 4. Database & CLI Enhancements
- `php papastro migrate:status`: Pretty tabular display of all migrations, batch numbers, run statuses, and execution timestamps.
- `php papastro migrate:fresh`: Drops all database tables and runs migrations from scratch, with optional `--seed` flag.
- `php papastro migrate:reset`: Rolls back all database migration batches in reverse order.
- `php papastro queue:work`: Queue worker daemon processing background jobs with `--queue`, `--sleep`, and `--tries`.
- `php papastro queue:failed`: Displays all failed background jobs with stack trace errors.
- `php papastro queue:retry`: Retries a specific failed job by ID or all failed jobs with `--all`.
- `php papastro queue:clear`: Clears pending jobs from the queue store.
- `php papastro down`: Enables maintenance mode with custom secret bypass, custom message, and retry duration.
- `php papastro up`: Brings the application out of maintenance mode.
- `PreventRequestsDuringMaintenance` middleware with bypass cookie, secret URL (`/{secret}`), and header (`X-Maintenance-Secret`) support.

---

## [2.0.0] - 2026-11-10 — *Enterprise Core Hardening & Security*

### Added
- **Session Subsystem** (`Papastro\Session\Session`):
  - SAPI-agnostic session store decoupled from native `session_start()`.
  - Compatible with long-running workers (FPM, CLI, Octane, Swoole).
  - Cookie lifecycle managed via `StartSession` middleware.
- **Security & Cryptography Suite**:
  - `Hash`: Argon2id and Bcrypt password hashing with verification and rehash checks.
  - `Encrypter`: AES-256-CBC cipher with HMAC-SHA256 authenticated payload encryption.
  - `RateLimiter`: Sliding-window and decay rate limiting.
  - Security Middleware: `ThrottleRequests`, `SecurityHeaders` (CSP, HSTS, X-Frame-Options, X-Content-Type-Options), `HandleCors` (configurable CORS headers).
  - CSRF Protection: `VerifyCsrfToken` middleware with constant-time token comparison.
- **Caching Engine** (`Papastro\Cache\CacheManager`):
  - Multi-driver caching: File sharded cache, Memory L1 cache, APCu cache.
  - Atomic locking with `lock($key, $seconds)`.
- **Structured Logging** (`Papastro\Log\Logger`):
  - PSR-3 compliant logging with JSON context hydration and log rotation.
- **Queue Subsystem** (`Papastro\Queue\QueueManager`):
  - Multi-driver queue engine (Sync, File) with failure tracking and automatic retry.
- **Active Record ORM Improvements**:
  - Identifier sanitization (`Security::sanitizeSqlIdentifier`).
  - Whitelisted SQL comparison operators.
  - Query cloning on aggregate methods (`count()`, `max()`, `min()`).
  - Dynamic Eloquent-style accessors (`get*Attribute`) and mutators (`set*Attribute`).
  - Auto-reconnect on dropped PDO connections (`checkConnection`).

### Fixed
- Fixed recursive balanced-parenthesis regex in `View/Compiler.php` to correctly support nested directives such as `@if(!empty($error))`, `@unless`, and `@forelse`.
- Resolved session header conflict under CLI and long-running runtime environments.

---

## [1.3.0] - 2026-10-15 — *Modular Monolith Optimization*

### Added
- Fast static route hash map resolution in `RouteCollection` (O(1) lookup).
- Module route caching (`php papastro route:cache`).
- Island architecture rendering optimizations with lazy hydration directives (`client:load`, `client:idle`, `client:visible`).
- Automated Sitemap generator (`php papastro seo:sitemap`).
- Automated PWA manifest & Service Worker precaching (`php papastro pwa:generate`).

---

## [1.2.0] - 2026-08-07 — *Initial Open Source Release*
- Initial public release of Papastro Framework by **kangpcode (Dhafa Nazula Permadi)**.
- Hybrid PHP + Astro Island architecture.
- Modular Monolith design.
