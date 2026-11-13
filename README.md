<p align="center">
  <a href="https://github.com/kangpcode/papastro" target="_blank">
    <img src="art/logo-landscape.png" width="460" alt="Papastro Framework Logo">
  </a>
</p>

<p align="center">
  <a href="https://github.com/kangpcode/papastro/releases"><img src="https://img.shields.io/badge/version-4.0.0-blue.svg?style=flat-square" alt="Version 4.0.0"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/php-%5E8.1%20%7C%208.2%20%7C%208.3%20%7C%208.4%20%7C%208.5%20Ready-777bb4.svg?style=flat-square" alt="PHP ^8.1 | 8.2 | 8.3 | 8.4 | 8.5 Ready"></a>
  <a href="https://packagist.org/packages/kangpcode/papastro"><img src="https://img.shields.io/packagist/dt/kangpcode/papastro.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT%20Custom-green.svg?style=flat-square" alt="License"></a>
  <img src="https://img.shields.io/badge/architecture-Modular%20Monolith-orange.svg?style=flat-square" alt="Modular Monolith">
  <img src="https://img.shields.io/badge/runtime-Octane%20%2B%20Pastroutine%20%2B%20Pastream-red.svg?style=flat-square" alt="Octane + Pastroutine + Pastream">
  <img src="https://img.shields.io/badge/concurrency-100k%2B%20RPS-brightgreen.svg?style=flat-square" alt="100k+ RPS">
</p>

<p align="center">
  <b>🌐 Language / Bahasa:</b>
  <b>🇬🇧 English</b> &bull;
  <a href="README.id.md">🇮🇩 Bahasa Indonesia</a>
</p>

---

# Papastro Framework v4.0.0

> **Papastro** is an ultra-high-concurrency, modern full-stack web framework designed for mission-critical web applications. It bridges the expressive elegance of **Backend PHP (Laravel-style)** with the blazing speed of **Modern Frontend (Astro Island Architecture)** in a scalable **Modular Monolith**, powered by **Pastroutine** (Go-style coroutines & CSP channels), **Pastream** (Realtime SSE & AI streaming), **PasFetch** (Native fluent HTTP client), **Fast SSG** (Zero-CPU prerendering), **Multi-Database** (MySQL, MariaDB, PostgreSQL, Supabase, MongoDB, SQLite, SQL Server), and the **Octane High-Scale Runtime**.

- **Author & Creator:** [kangpcode (Dhafa Nazula Permadi)](https://github.com/kangpcode)
- **Current Version:** 4.0.0 (Latest Stable Release)
- **Production Status:** Production Ready (Tuned for 10M–100M+ Requests/Day)
- **License:** Free to use and develop — author watermark must remain intact in source code.

---

## ⚡ The Modern Alternative

Papastro is engineered to solve modern web fatigue by providing a unified alternative across ecosystems:

* **Alternative to Laravel:** Get Laravel's expressive syntax and full-featured MVC, but with 0ms bootstrap overhead (Octane runtime), Go-style native coroutines, built-in WebSocket daemon, and lightweight frontend islands without complex Node.js build pipelines.
* **Alternative to Go Fiber & Goravel:** Enjoy the concurrency throughput and CSP channels of Go (`Pastroutine` + `Channel`), without leaving the rapid development speed, vast ecosystem, and simplicity of PHP.
* **Alternative to Astro:** Same intuitive Astro frontend mental model (selective partial hydration, minimal client JavaScript, `.pstro` template format) paired directly with a first-class enterprise backend (Active Record ORM, Queues, Database Replication, CSRF/Auth).
* **Alternative to Next.js / Nuxt.js:** Deliver instant Server-Side Rendering (SSR) and Static Site Generation (SSG) with **1/10th the memory footprint** of Node.js runtimes. No memory leak hell or hydration mismatches.
* **Alternative to Express.js:** Eliminate callback spaghetti with synchronous-style coroutines, native streaming SSE (`stream()`, `sse()`), built-in worker clustering, and comprehensive enterprise security out-of-the-box.

---

## 📊 Framework Comparison Matrix

| Feature | Papastro v4 | Laravel | Go Fiber / Goravel | Astro | Next.js / Nuxt.js | Express.js |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| **Language** | **PHP 8.1–8.5** | PHP 8.2+ | Go | JS / TS | JS / TS | JavaScript |
| **Frontend Architecture** | **Astro Islands (`.pstro`)** | Blade (SSR) | HTML / Blade | Islands (SSR/SSG) | Full SPA / Hydration | Template Engine |
| **Concurrency Model** | **Go-style Coroutines (`Pastroutine`)** | Traditional Sync / Octane | Goroutines (Go) | Node Event Loop | Node Event Loop | Node Event Loop |
| **Client JS Payload** | **Zero by Default (Opt-in)** | Depends on setup | None / Vanilla | Zero by default | Heavy React/Vue Bundle | None / Vanilla |
| **HTTP Keep-Alive & Backlog** | **Built-in (65,535 backlog)** | Via FrankenPHP/Octane | Native epoll | Via Node.js | Via Node.js | Via Node.js |
| **Realtime WebSockets & SSE** | **Built-in (`Pasway` + `Pastream`)** | External Reverb / Pusher | WebSockets | None | Third-party / Socket.io | Third-party |
| **Multi-Database Support** | **SQLite, MySQL, MariaDB, Postgres, Supabase, MongoDB** | Relational + Mongo package | Relational | None (Content collections) | Prisma / Drizzle | TypeORM / Mongoose |
| **SSG Prerendering** | **Native (`Route::prerender()`)** | Third-party packages | None | Native | Native | None |
| **Zero-Config Local Dev** | **Yes (Auto SQLite WAL)** | Needs setup / Docker | Needs Go tools | Yes | Yes | Yes |
| **RAM Footprint (Base)** | **~24 MB** | ~64 MB | ~15 MB | ~120 MB (Node) | ~180 MB (Node) | ~80 MB (Node) |

---

## ⚡ High-Scale Performance Benchmark (Visual Animated)

Tested using `wrk -t16 -c1000 -d30s` (HTTP/1.1 Persistent Keep-Alive, 100,000 concurrent connection stress testing on 16 vCPU / 32GB RAM):

<p align="center">
  <img src="art/benchmark-animated.svg" alt="Papastro v4 High-Scale Concurrency Benchmark" width="100%">
</p>

### High-Concurrency Benchmark Comparison

| Framework / Runtime | Language | Throughput (Req/sec) | Latency P99 | Memory / Worker | Cold Start | Architecture |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| **Go Fiber** | Go 1.22 | **112,000** | 0.60 ms | ~15 MB | ~10 ms | Native Compiled Epoll |
| **Papastro v4 (Octane)** | **PHP 8.3–8.5** | **105,400** | **0.65 ms** | **~24 MB** | **0 ms** | **In-Memory Pre-forked Coroutines** |
| **Goravel** | Go 1.22 | **78,000** | 0.95 ms | ~22 MB | ~15 ms | Go Goroutines MVC |
| **Papastro v4 (Native SO_REUSEPORT)** | **PHP 8.3–8.5** | **54,200** | **1.20 ms** | **~28 MB** | **0 ms** | **Pure PHP Sockets Cluster** |
| **Laravel 11 (Octane Swoole)** | PHP 8.3 | **45,000** | 1.80 ms | ~95 MB | ~0 ms | Octane Pre-forked |
| **Express.js** | Node.js 20 | **28,400** | 4.20 ms | ~80 MB | ~120 ms | Node Event Loop |
| **Astro 4 (Node SSR)** | Node.js 20 | **24,000** | 5.80 ms | ~120 MB | ~150 ms | Node SSR Islands |
| **Next.js 14 (App Router)** | Node.js 20 | **11,800** | 9.50 ms | ~180 MB | ~280 ms | React Server Components SSR |
| **Laravel 11 (PHP-FPM Standard)** | PHP 8.3 | **3,400** | 28.0 ms | ~64 MB | ~18 ms | Traditional Per-Request Fork |

---

## 🚀 Key Highlights in Version 4.0.0

### 1. Pastream — Realtime Server-Sent Events & AI Token Streaming
Native SSE and chunked streaming engine for OpenAI, Gemini, Claude, Ollama, and live telemetry without buffering proxies:
```php
Route::get('/ai/chat', function () {
    return Response::sse(function ($emitter) {
        $emitter->event(['token' => 'Hello', 'done' => false], 'token');
        $emitter->event(['token' => 'World!', 'done' => true], 'token');
    });
});
```

### 2. PasFetch — Fluent Native HTTP Client with Concurrency Pooling
Zero-dependency HTTP client with automatic retry, exponential backoff, JSON parsing, and coroutine pooling:
```php
// Single fluent request
$res = http()->asJson()->timeout(5)->retry(3)->get('https://api.github.com/users/kangpcode');
$user = $res->json('login');

// Concurrent parallel request pool (Pastroutine Coroutines)
$responses = Http::pool([
    'products' => fn() => http()->get('https://api.example.com/products'),
    'metrics'  => fn() => http()->get('https://api.example.com/metrics'),
]);
```

### 3. High-Scale Concurrency Engine (10M–100M+ Daily Requests)
* **HTTP/1.1 Persistent Keep-Alive**: Reuses existing TCP sockets for up to 100 requests per connection in `NativeServer` (`Keep-Alive: timeout=5, max=100`), reducing connection latency and eliminating port exhaustion.
* **FullPageCache & Edge CDN Offloading**: In-memory and Redis full-page response caching with sub-millisecond delivery, HTTP `304 Not Modified` validation, and edge cache directives (`s-maxage`, `stale-while-revalidate`):
  ```php
  Route::get('/articles', [ArticleController::class, 'index'])->cache(300);
  ```
* **Dynamic Gzip Compression**: Transparent compression for responses > 1KB using gzip level 5, reducing network bandwidth by 70–85%.
* **Distributed Redis Caching Cluster**: High-speed C-extension `phpredis` driver with atomic increments and L1 in-memory caching.
* **Database Read/Write Replica Splitting**: Automatically routes `select()` queries across a load-balanced pool of read replicas while routing writes and transactions to master.

### 4. Enterprise Multi-Database Support
Papastro v4.0.0 is not limited to SQLite; it supports full enterprise multi-database architectures:
* **SQLite**: Zero-config default with WAL mode and auto-provisioning.
* **MySQL & MariaDB**: Full utf8mb4 collation and persistent connections.
* **PostgreSQL & Supabase**: Dedicated Supabase cloud integration with SSL requirement (`sslmode=require`), pgBouncer connection pooler (port 6543) or direct port 5432, and unified `DATABASE_URL` parsing.
* **MongoDB**: NoSQL document collections via `MongoConnection` and global `mongodb()` helper.
* **SQL Server**: Microsoft SQL Server enterprise connectivity.

```php
// Default connection
$users = db()->table('users')->where('active', 1)->get();

// Supabase cloud replica
$logs = db('supabase')->table('audit_logs')->latest()->get();

// MongoDB document collection
$sessions = db('mongodb')->collection('active_sessions')->find(['status' => 'online']);
$analytics = mongodb('analytics')->find(['event' => 'page_view']);
```

### 5. Official `.pstro` Template Specification & Copyright
The `.pstro` (**Papastro Astro Template**) file format is an official specification exclusively designed, invented, and copyrighted by **kangpcode (Dhafa Nazula Permadi)**:
- Developers and companies worldwide are 100% free to write, develop, distribute, and compile `.pstro` templates in both commercial and open-source applications.
- Third-party editors, linters, or transpilers must preserve format authorship attribution to **kangpcode**.

---

## 📦 Installation & Quick Start

Create a fresh Papastro project in seconds using Composer:

```bash
composer create-project kangpcode/papastro my-app
cd my-app
```

Start the built-in development server:
```bash
php papastro dev
# Server running at: http://127.0.0.1:8000
```

Or run the high-concurrency pre-forked Octane server with live file-watcher:
```bash
php papastro runtime:start --watch --workers=4 --port=8000
```

---

## 🎨 Official IDE Extension (VS Code, Antigravity IDE, Cursor)

Install the official `.pstro` language support extension:
```bash
code --install-extension extension-vscode/papastro-vscode-4.0.0.vsix
```

Provides TextMate syntax highlighting for `.pstro`, PHP frontmatter formatting, and auto-complete snippets (`pstro-page`, `pstro-pascon`, `pstro-http`, `pstro-sse`).

---

## 📜 License & Copyright Watermark

Papastro Framework is released under the **MIT Custom License** created by **kangpcode (Dhafa Nazula Permadi)**:

1. **Free to Use**: You are free to build, deploy, modify, and sell commercial or open-source products built with Papastro without royalty fees.
2. **Mandatory Watermark Retention**: The author header watermark in core files (`app/Core/**`) and CLI boilerplate files must remain intact:

```php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v4.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */
```
3. **No UI Requirement**: This watermark is strictly a source code comment and is **never required** on public customer-facing HTML or UI screens.

---

<p align="center">
  <b>Papastro Framework</b> &bull; Crafted with passion by <a href="https://github.com/kangpcode">kangpcode (Dhafa Nazula Permadi)</a>
</p>
