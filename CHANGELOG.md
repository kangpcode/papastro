# Changelog — Papastro Framework

All notable changes to the Papastro Framework are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Author: **kangpcode (Dhafa Nazula Permadi)**
License: Free to use & modify. Watermark must remain in source code.

---

## [4.0.0] - 2026-11-13 — *Pastream SSE, PasFetch HTTP Client, Fast SSG & Zero-Config SQLite*

### Added

#### 1. Pastream (Realtime SSE & Streaming Response Engine)
- **Streaming & Server-Sent Events** (`Papastro\Http\StreamResponse`):
  - Native streaming chunked transfer responses and SSE support for real-time AI token generation (ChatGPT, Gemini, Claude), live logs, and metrics.
  - Automatic `Cache-Control: no-cache, no-transform`, `X-Accel-Buffering: no` headers for seamless proxy pass-through (Nginx, Caddy, Apache).
  - Global helpers `stream(callable $callback)` and `sse(callable $callback)`.

#### 2. PasFetch (Lightweight Native Fluent HTTP Client)
- **Zero-Dependency Native HTTP Client** (`Papastro\Http\Client\Http`, `PendingRequest`, `Response`):
  - Fluent request builder: `http()->asJson()->withToken($token)->get($url)`.
  - Automatic JSON serialization, URL query formatting, form data encoding, timeouts, and automatic retry mechanism with backoff.
  - Concurrent multi-request dispatching with `Http::pool()` powered by Pastroutine coroutines.
  - Global helper `http()`.

#### 3. Fast SSG & Prerendering (Astro-style Hybrid Architecture)
- **Static Site Generation Engine** (`Papastro\SSG\Prerenderer`):
  - Statically pre-computes HTML for static and island routes into `public/` for instantaneous microsecond delivery.
  - Fluent route registration: `Route::get('/about', ...)->prerender()`.
  - CLI command: `php papastro prerender [uris...]` and automated integration in `php papastro build`.

#### 4. Expanded Pascon Standalone SVG Vector Icons
- Added new modern icons: `stream`, `server`, `cloud`, `git`, `folder`, `file`, `bell`, `calendar`, `lock`, `unlock`, `sun`, `moon`, `download`, `upload`, `filter`.
- Fully scalable, customizable stroke and dimensions with `<pascon:... />` and `@pascon(...)`.

#### 5. Composer `create-project` & Zero-Config SQLite
- Defaults to zero-config SQLite with Write-Ahead Logging (WAL) mode for immediate local development without MySQL setup.
- Dual autoloader support seamlessly detecting `libs/autoload.php` and `vendor/autoload.php`.
- Complete interactive setup wizard `php papastro setup`.

#### 6. High-Scale Concurrency & Edge CDN Acceleration (10M–100M+ Scale)
- **HTTP/1.1 Persistent Keep-Alive**:
  - Pre-forking `NativeServer` maintains open TCP sockets for up to 100 requests per connection (`Keep-Alive: timeout=5, max=100`), reducing connection latency and eliminating port exhaustion.
  - Maximum socket backlog of `65535` configured across `NativeServer` and `SwooleServer`.
- **FullPageCache & Edge CDN Offloading** (`App\Http\Middleware\FullPageCache`):
  - In-memory and Redis full-page response caching with sub-millisecond delivery.
  - Automatic `ETag` generation with HTTP `304 Not Modified` handling (zero-byte payload).
  - Edge cache directives (`s-maxage`, `stale-while-revalidate`) enabling global CDN absorption (Cloudflare, Fastly, AWS CloudFront).
  - Fluent route macro: `Route::get('/news', ...)->cache(300)`.
- **Dynamic Gzip Response Compression** (`App\Http\Middleware\CompressResponse`):
  - Transparent compression for responses > 1KB using gzip level 5, reducing network bandwidth by 70–85%.
- **Immutable Static Asset Caching**:
  - Static files (CSS, JS, images, fonts) served with `Cache-Control: public, max-age=31536000, immutable` and ETag validation.

#### 7. Distributed Redis Caching & Multi-Node DB Read/Write Replicas
- **Redis Cache Driver** (`Papastro\Cache\CacheManager`):
  - C-extension `phpredis` support with L1 in-memory caching and atomic `incrBy`/`expire` operations.
- **Database Read/Write Replica Splitting** (`Papastro\ORM\Connection`):
  - Automatic routing of `select()` queries across a load-balanced pool of read replicas.
  - Strict master routing for `insert`, `update`, `delete`, and transaction blocks.

#### 8. Resilience, Anti-Crash & Developer Ergonomics
- **Reserved Memory Headroom (Anti-OOM)**:
  - 32KB memory reservation at boot released on shutdown so fatal Out-Of-Memory conditions log and report gracefully instead of crashing abruptly.
- **Auto-Provisioning SQLite Database**:
  - Automatically provisions directory and file for SQLite databases on first run if missing.
- **New CLI Command**:
  - `php papastro route:clear` for quick route cache invalidation.

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

#### 5. Auto-Update & Migration Engine (`php papastro upgrade`)
- **Non-Destructive Core Synchronizer**:
  - Safe, automatic upgrade path for applications running on Papastro v1.0.0, v1.1.0, v1.2.0, or v2.0.0.
  - Automatically synchronizes core framework components (`app/Core/`, CLI tools, runtime server scripts).
  - **Zero Data Loss Guarantee**: Strictly isolates and preserves all user modules in `app/Modules/`, custom routes in `routes/`, custom views in `resources/`, database data, and `.env` credentials without overwriting any user modifications.
  - Automatic migration runner and cache rebuilder.
- **Backward Compatibility Layer**:
  - Seamless support for legacy view directives and island tags.
  - Added Laravel-style helper functions: `bcrypt($value)`, `encrypt($value)`, `decrypt($payload)`.
- **Concurrency Health Self-Test**:
  - `php papastro pastroutine:test`: Live benchmark and concurrency verification tool testing 1,000+ coroutines, channels, mutex, and waitgroups.
- **Official Brand Assets**:
  - Added primary icon logo (`art/logo-icon.png`) and landscape banner logo (`art/logo-landscape.png`).
  - Integrated brand assets into welcome landing page, base layout, and repository documentation.

#### 6. PHP 8.4 & PHP 8.5 Enterprise Compatibility
- **Explicit Type Declarations**: 100% compliant with PHP 8.4 deprecation rules regarding implicit nullable parameters (`?Type $param = null` used everywhere).
- **Zero Dynamic Property Warnings**: Fully declared class properties across all core subsystems (`Application`, `Container`, `Model`, `Request`, `Response`, `Router`, `Scheduler`, `Pastroutine`).
- **Multi-Version CI Matrix**: Automated GitHub Actions workflow testing on PHP 8.1, 8.2, 8.3, 8.4, and PHP 8.5.

### Fixed
- **Pastroutine Error Isolation**:
  - Handled errors via `->catch()` or `->wait()` no longer produce false-alarm error logs in `storage/logs/papastro.log`.
  - Added fluent `->catch(callable $fn)` and `->then(callable $onSuccess, ?callable $onFailure)` API for promises-style concurrency handling.
- **User Authentication Null Safety**:
  - `User::findByEmail(?string $email)` now gracefully handles null or empty inputs without throwing `TypeError`.
- **View Compiler Comments**:
  - Compiler now properly parses frontmatter blocks preceded by HTML comments and whitespace.

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
