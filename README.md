# 🌟 Papastro Framework

> **Papastro** = **PAPA** (PHP Architecture Pattern Approach) + **ASTRO** (Astro-style island frontend)

A hybrid **PHP backend (Laravel-style)** + **frontend (Astro-style island architecture)** framework with a **Modular Monolith Scalable** architecture.

**Author:** kangpcode (Dhafa Nazula Permadi)  
**Version:** 1.0.0  
**License:** Free to use & modify — watermark must remain in source

---

## ✨ Features

| Feature | Description |
|---------|-------------|
| 🏝️ **Island Architecture** | SSR by default, JS islands hydrate selectively (`client:load`, `client:idle`, `client:visible`, `client:only`) |
| 📦 **Modular Monolith** | Domain-driven modules, each with its own MVC + Routes + Islands + Pages |
| 🚀 **Expressive Router** | Laravel-style routing with named routes, groups, middleware, resource routes |
| 🗄️ **Eloquent-like ORM** | Active Record model with QueryBuilder, migrations, and schema builder |
| 🔍 **SEO-First** | Auto meta tags, Open Graph, Twitter Card, JSON-LD, canonical URL, sitemap generator |
| 📱 **PWA Ready** | Service worker, manifest.json, offline caching — generated with one command |
| 🛠️ **Powerful CLI** | 30+ commands: module scaffolding, generators, migrations, debug, optimization |
| 🐛 **Debug Experience** | Debugbar (Laravel-style) + Dev Overlay (Next.js-style) |
| 💉 **IoC Container** | Full dependency injection with constructor auto-wiring |
| 🔧 **Service Providers** | Register/boot lifecycle for clean application bootstrapping |

---

## 🚀 Quick Start

```bash
# Install via Composer
composer create-project kangpcode/papastro my-app

cd my-app

# Setup environment
cp .env.example .env
php papastro key:generate

# Run migrations
php papastro migrate

# Start development server
php papastro dev
# → http://127.0.0.1:8000
```

---

## 📁 Directory Structure

```
papastro-app/
├── app/
│   ├── Core/               # Framework kernel (do not modify)
│   │   ├── Container/      # IoC Container
│   │   ├── Console/        # CLI Kernel + Commands
│   │   ├── Debugger/       # Debugbar + QueryCollector
│   │   ├── Foundation/     # Application, ServiceProvider, Bootstrap
│   │   ├── Http/           # Request, Response, Kernel, Pipeline
│   │   ├── Island/         # IslandRenderer + HydrationEngine
│   │   ├── Module/         # ModuleLoader + ModuleManager
│   │   ├── ORM/            # Model, QueryBuilder, Connection, Migrator
│   │   ├── PWA/            # PwaManager
│   │   ├── Router/         # Router, Route, RouteCollection, Facade
│   │   ├── SEO/            # SeoManager, SitemapGenerator
│   │   ├── View/           # ViewEngine, Compiler (.pstro parser)
│   │   └── helpers.php     # Global helper functions
│   ├── Console/            # App Console Kernel
│   ├── Http/               # App HTTP Kernel + Middleware
│   ├── Modules/            # Your domain modules
│   │   ├── User/           # Authentication module
│   │   └── Blog/           # Blog module (starter)
│   └── Providers/          # App, Route, Database, Module providers
├── bootstrap/              # Application bootstrap
├── config/                 # app, database, modules, seo, pwa
├── database/
│   ├── migrations/         # Database migrations
│   └── seeders/            # Database seeders
├── public/
│   ├── index.php           # HTTP entry point
│   ├── app.css             # Main stylesheet
│   ├── islands/            # Built island JS files (public)
│   ├── manifest.json       # PWA manifest (auto-generated)
│   └── sw.js               # Service worker (auto-generated)
├── resources/
│   ├── islands/            # Global island components
│   ├── layouts/            # Layout templates (.pstro)
│   └── views/              # Global view templates (.pstro)
├── routes/
│   ├── web.php             # Global web routes
│   └── api.php             # Global API routes
├── storage/
│   ├── cache/              # Compiled views & route cache
│   ├── debug/              # Debug snapshots
│   └── logs/               # Application logs
├── .env.example            # Environment template
├── papastro                # CLI executable
├── papastro.php            # CLI bootstrap
└── composer.json
```

---

## 🏝️ Island Architecture

File extension: `.pstro` (Papastro template)

```pstro
---
// Frontmatter: runs on the server (PHP)
use App\Modules\Blog\Models\Post;
$posts = Post::published()->latest()->paginate(10);
---
@layout('layouts.base')

@section('content')
<h1>Blog</h1>

@foreach ($posts['data'] as $post)
  <a href="/blog/{{ $post->slug }}">{{ $post->title }}</a>
@endforeach

<!-- This island loads JS only when visible in viewport -->
<CommentBox client:visible postId="{{ $post->id }}" />
@endsection
```

### Hydration Directives

| Directive | Behavior |
|-----------|----------|
| `client:load` | Hydrate immediately on page load |
| `client:idle` | Hydrate when browser is idle (`requestIdleCallback`) |
| `client:visible` | Hydrate when element enters viewport (`IntersectionObserver`) |
| `client:only` | Skip SSR, full client-side render only |

---

## 🛠️ CLI Reference

```bash
# Development
php papastro dev                      # Start dev server (port 8000)
php papastro build                    # Build for production
php papastro serve                    # Start production server

# Module Management
php papastro module:make Blog         # Scaffold a new module
php papastro module:enable Blog       # Enable a module
php papastro module:disable Blog      # Disable a module
php papastro module:list              # List all modules

# Code Generators (auto-include Papastro watermark)
php papastro make:controller Blog/PostController --resource
php papastro make:model Blog/Post
php papastro make:migration create_comments_table
php papastro make:island Blog/CommentBox
php papastro make:page Blog/index
php papastro make:middleware AuthCheck
php papastro make:service Blog/PostService

# Database
php papastro migrate                  # Run pending migrations
php papastro migrate:rollback         # Rollback last batch
php papastro db:seed                  # Run database seeders

# SEO & PWA
php papastro seo:sitemap              # Generate sitemap.xml
php papastro pwa:generate             # Generate manifest.json + sw.js
php papastro pwa:precache             # Update service worker precache

# Debug & Diagnostics
php papastro debug:route              # List all routes
php papastro debug:island             # Find all island files
php papastro debug:clear              # Clear debug snapshots

# Cache & Optimize
php papastro cache:clear              # Clear view/route/config cache
php papastro optimize                 # Optimize the application
php papastro route:cache              # Cache route list
php papastro route:list               # Display all routes

# Application
php papastro key:generate             # Generate APP_KEY
```

---

## 🗄️ ORM Usage

```php
use App\Modules\Blog\Models\Post;

// Find all
$posts = Post::all();

// Query builder
$posts = Post::where('published', 1)
              ->latest()
              ->paginate(10, $page);

// Create
$post = Post::create([
    'title'   => 'Hello World',
    'body'    => 'My first post!',
    'user_id' => 1,
]);

// Find by ID
$post = Post::find(1);
$post = Post::findOrFail(42); // throws if not found

// Update
$post->update(['title' => 'Updated Title']);

// Delete
$post->delete();

// Aggregates
$count = Post::where('published', 1)->count();
$latest = Post::latest()->limit(5)->get();
```

---

## 🔍 SEO

```php
// In a controller or page
app(\Papastro\SEO\SeoManager::class)
    ->title('My Blog Post')
    ->description('A great article about...')
    ->canonical('https://mysite.com/blog/my-post')
    ->set(['og_image' => '/images/post.jpg']);

// In a .pstro template
{!! \Papastro\SEO\SeoManager::schema('Article', $post) !!}

// Generate sitemap
php papastro seo:sitemap
```

---

## 📱 PWA

Configure in `config/pwa.php`:

```php
return [
    'name'       => 'My App',
    'short_name' => 'App',
    'theme_color' => '#0f172a',
    'strategy'   => 'cache-first', // or network-first, stale-while-revalidate
    'precache'   => ['/', '/offline', '/blog'],
];
```

```bash
php papastro pwa:generate   # Creates public/manifest.json + public/sw.js
```

---

## 🐛 Debug

Set in `.env`:

```env
APP_DEBUG=true
PAPASTRO_DEBUGBAR=true
PAPASTRO_DEV_OVERLAY=true
```

- **Debugbar** — bottom panel with SQL queries, route info, request data, timeline
- **Dev Overlay** — full-screen error overlay with stack trace on exceptions
- **Keyboard shortcut** — `Alt+D` to toggle debugbar

---

## 📜 License & Watermark

Papastro is **free to use and modify** for any purpose.  
**One condition**: The copyright watermark in source files **must remain** (in code comments — never shown in public HTML).

```php
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
```

---

## 🗺️ Roadmap

- [x] **Phase 1** — Core Kernel (Container, Router, ORM, CLI)
- [x] **Phase 2** — Module System (auto-discovery, enable/disable)
- [x] **Phase 3** — View & Island Engine (.pstro parser, hydration)
- [x] **Phase 4** — Debugger (Debugbar + Dev Overlay)
- [x] **Phase 5** — SEO & PWA (sitemap, JSON-LD, manifest, SW)
- [x] **Phase 6** — Tooling (build, optimize, cache, migrate)
- [x] **Phase 7** — Documentation & Starter Kit
- [ ] **Phase 8** — Event system (Events + Listeners)
- [ ] **Phase 9** — Validation engine
- [ ] **Phase 10** — Auth guards + session management

---

*Made with ❤️ by kangpcode (Dhafa Nazula Permadi)*
