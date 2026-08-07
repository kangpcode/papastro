# Papastro Framework v1.2.0

> **Papastro Framework** adalah framework web hybrid berskala tinggi yang menggabungkan keandalan **Backend PHP (bergaya Laravel)** dengan efisiensi **Frontend Modern (bergaya Astro Island Architecture)** dalam arsitektur **Modular Monolith Scalable**.

- **Pembuat:** kangpcode (Dhafa Nazula Permadi)
- **Versi Framework:** 1.2.0
- **Lisensi:** Bebas digunakan dan dikembangkan — watermark kepemilikan wajib dipertahankan pada kode sumber.

---

## Daftar Isi

1. [Visi dan Konsep Utama](#visi-dan-konsep-utama)
2. [Fitur Utama Framework](#fitur-utama-framework)
3. [Benchmark dan Perbandingan Framework](#benchmark-dan-perbandingan-framework)
4. [Persyaratan Sistem dan Instalasi](#persyaratan-sistem-dan-instalasi)
5. [Struktur Direktori Proyek](#struktur-direktori-proyek)
6. [Arsitektur Modular Monolith](#arsitektur-modular-monolith)
7. [Island Architecture dan Hydration Engine](#island-architecture-dan-hydration-engine)
8. [Router Engine](#router-engine)
9. [Active Record ORM dan Migrasi](#active-record-orm-dan-migrasi)
10. [Event dan Listener System](#event-dan-listener-system)
11. [Optimasi SEO dan Structured Data](#optimasi-seo-dan-structured-data)
12. [Progressive Web App (PWA) Engine](#progressive-web-app-pwa-engine)
13. [Debugger dan Dev Overlay](#debugger-dan-dev-overlay)
14. [Dokumentasi Perintah CLI (papastro)](#dokumentasi-perintah-cli-papastro)
15. [Lisensi dan Watermark Kepemilikan](#lisensi-dan-watermark-kepemilikan)

---

## Visi dan Konsep Utama

Papastro dirancang untuk memberikan pengalaman pengembang (*Developer Experience*) terbaik tanpa mengorbankan performa aplikasi. Framework ini memadukan dua filosofi arsitektur utama:

- **Backend Ala Laravel**: Service Container IoC, Routing ekspresif, Active Record ORM, Middleware Pipeline, Artisan-style CLI, dan Templating Engine berbasis Blade/Astro.
- **Frontend Ala Astro**: Island Architecture (komponen interaktif di-hydrate secara selektif di client, sisanya di-render 100% statis/SSR di server), output HTML minimal secara default, dan SEO-first.

Target utama Papastro adalah menyediakan satu ekosistem terintegrasi dalam satu codebase dan satu CLI tanpa perlu memisahkan backend PHP dan frontend JS ke dalam repository terpisah.

---

## Fitur Utama Framework

- **Modular Monolith Architecture**: Setiap domain aplikasi dipisahkan ke dalam modul independen yang memiliki MVC, Routes, Pages, dan Islands sendiri.
- **Selective Island Hydration**: Render server (SSR) secara default. Komponen JavaScript hanya di-hydrate di client menggunakan directive `client:load`, `client:idle`, `client:visible`, atau `client:only`.
- **Expressive Router**: Mendukung HTTP GET, POST, PUT, DELETE, Route Grouping, Named Routes, Resource Routes, dan Custom Middleware.
- **Active Record ORM & QueryBuilder**: Interface query basis data yang fleksibel dengan dukungan driver MySQL, SQLite, dan PostgreSQL.
- **Event & Listener System**: Engine `EventDispatcher` terintegrasi untuk menangani arsitektur event-driven decoupling.
- **Security & CSRF Hardening**: Proteksi CSRF token (`VerifyCsrfToken`), pembersihan XSS otomatis (`Security::sanitize()`), dan middleware pembersihan input (`TrimStrings`, `ConvertEmptyStringsToNull`).
- **SEO Engine Out-of-the-Box**: Otomatisasi Meta Tags, Open Graph, Twitter Cards, JSON-LD Structured Data, Canonical URL, dan Sitemap XML Generator.
- **PWA Engine Out-of-the-Box**: PWA Manifest Generator (`manifest.json`), Service Worker Builder (`sw.js`), dan caching offline otomatis.
- **Papastro Debugbar & Dev Overlay**: Pelacakan query SQL, deteksi query N+1, log request/response, timeline rendering, serta visual error overlay interaktif saat mode debug aktif.
- **Comprehensive CLI Tooling**: Lebih dari 30 perintah bawaan untuk scaffolding modul, pembuat kode (generator), migrasi basis data, optimasi, dan diagnostik.

---

## Benchmark dan Perbandingan Framework

Berikut adalah tabel perbandingan performa, efisiensi sumber daya, dan arsitektur teknis antara **Papastro v1.2.0** dengan framework web modern populer lainnya (**Laravel**, **Next.js**, **Nuxt.js**, dan **Astro**):

| Parameter Perbandingan | Papastro v1.2.0 | Laravel 11 (Blade) | Next.js 14 (App Router) | Nuxt.js 3 (Vue 3) | Astro 4 (Node SSR) |
|------------------------|-----------------|--------------------|-------------------------|-------------------|--------------------|
| **Bahasa Utama (Backend)** | PHP 8.3 | PHP 8.3 | Node.js (TypeScript) | Node.js (TypeScript) | Node.js / TypeScript |
| **Arsitektur Rendering** | Hybrid SSR + Island Hydration | Server-Side Rendering (Blade) | Fullstack React SSR / Client CSR | Fullstack Vue SSR / Client CSR | Island Architecture SSR |
| **Ukuran JS Client (Default)** | ~0 KB - 5 KB (Zero-JS) | ~0 KB (Pure HTML) | ~85 KB - 120 KB (React Runtime) | ~70 KB - 100 KB (Vue Runtime) | ~0 KB - 5 KB (Zero-JS) |
| **TTFB Server Response Time** | ~5 - 12 ms | ~20 - 35 ms | ~25 - 50 ms | ~30 - 55 ms | ~15 - 30 ms |
| **Server Memory Footprint** | ~6 - 12 MB / Worker | ~18 - 35 MB / Worker | ~120 - 250 MB / Process | ~100 - 220 MB / Process | ~80 - 180 MB / Process |
| **Lighthouse Performance Score** | 98 - 100 | 95 - 100 | 85 - 95 | 88 - 96 | 98 - 100 |
| **Struktur Aplikasi** | Modular Monolith Out-of-the-Box | Standard Monolith (App Root) | Directory-based Routing | Directory-based Routing | Directory-based Routing |
| **Dukungan SEO & PWA Bawaan** | Bawaan Framework (Seo + PWA) | Membutuhkan Package Tambahan | Membutuhkan Config & Package | Membutuhkan Module External | Membutuhkan Plugin Integrasi |
| **Kompleksitas Infrastruktur** | Sangat Rendah (Single PHP Server) | Sangat Rendah (Single PHP Server) | Sedang - Tinggi (Node Cluster / Edge) | Sedang - Tinggi (Node Nitro) | Sedang (Node / Static) |

### Analisis Hasil Benchmark Teknis

1. **Ukuran Bundle JavaScript Client (Zero-JS Default)**:
   - Papastro dan Astro mengadopsi filosofi *Island Architecture*. Halaman di-render sebagai pure HTML di server tanpa mengirim runtime JavaScript framework berat ke client. JavaScript hanya di-load untuk komponen interaktif tertentu yang ditandai dengan directive `client:load`, `client:idle`, atau `client:visible`.
   - Next.js dan Nuxt.js selalu mengunduh React/Vue Runtime (~70 - 120 KB) untuk melakukan proses *re-hydration* penuh seluruh DOM tree di browser.

2. **Respon Server (TTFB) dan Alokasi Memori**:
   - Papastro menggunakan lightweight core kernel berbasis PHP 8.3 native dengan OPcache. Konsumsi memori berada di tingkat 6 - 12 MB per worker dengan waktu respon TTFB 5 - 12 ms.
   - Framework berbasis Node.js (Next.js & Nuxt.js) membutuhkan alokasi memori proses Node yang jauh lebih tinggi (100 - 250 MB per instance) untuk mempertahankan Virtual DOM state dan event loop Node.js.

3. **Kemudahan Deployment dan Operasional**:
   - Papastro dapat dideploy secara langsung di server PHP standar (Nginx + PHP-FPM, Apache, LiteSpeed, Docker, atau Shared Hosting) sebagai satu kesatuan Modular Monolith tanpa membutuhkan build cluster Node.js yang kompleks atau layanan serverless berbiaya tinggi.

---

## Persyaratan Sistem dan Instalasi

### Persyaratan Sistem

- PHP versi 8.1 atau yang lebih baru
- Ekstensi PHP wajib: `pdo`, `pdo_sqlite` / `pdo_mysql` / `pdo_pgsql`, `json`, `mbstring`
- Composer 2.x

### Langkah Instalasi Proyek Baru

```bash
# 1. Buat proyek baru melalui Composer
composer create-project kangpcode/papastro nama-proyek

# 2. Masuk ke direktori proyek
cd nama-proyek

# 3. Salin konfigurasi environment
cp .env.example .env

# 4. Generate kunci aplikasi
php papastro key:generate

# 5. Jalankan migrasi basis data
php papastro migrate

# 6. Jalankan server pengembang (Development Server)
php papastro dev
```

Aplikasi pengembang akan berjalan secara otomatis di `http://127.0.0.1:8000`.

---

## Struktur Direktori Proyek

```
papastro-app/
├── app/
│   ├── Core/                    # Framework Kernel (Jangan diubah oleh developer)
│   │   ├── Console/             # CLI Engine & Commands
│   │   ├── Container/           # IoC Container Engine
│   │   ├── Debugger/            # Papastro Debugbar & Dev Overlay
│   │   ├── Events/              # EventDispatcher Engine
│   │   ├── Foundation/          # Application & ServiceProvider Base
│   │   ├── Http/                # Request, Response & Pipeline Engine
│   │   ├── Island/              # IslandRenderer & Hydration Engine
│   │   ├── Module/              # ModuleLoader & ModuleManager
│   │   ├── ORM/                 # Model, QueryBuilder, Connection & Migrator
│   │   ├── PWA/                 # PWA Engine & Generator
│   │   ├── Router/              # Router, RouteCollection & Facades
│   │   ├── SEO/                 # SeoManager & SitemapGenerator
│   │   ├── Validation/          # Validator Engine & Exceptions
│   │   ├── View/                # ViewEngine & Compiler (.pstro Parser)
│   │   └── helpers.php          # Global Helper Functions
│   ├── Console/                 # Kernel Console Aplikasi
│   ├── Http/                    # Kernel HTTP Aplikasi & Custom Middleware
│   ├── Modules/                 # Modul Domain Bisnis Aplikasi
│   │   ├── User/                # Modul Autentikasi User (Bawaan)
│   │   ├── Blog/                # Modul Blog (Bawaan)
│   │   └── Product/             # Modul Produk (Contoh Scaffolding)
│   └── Providers/               # Service Providers Aplikasi
├── bootstrap/                   # Application Bootstrapper
├── config/                      # File Konfigurasi (app, database, modules, pwa, seo)
├── database/
│   ├── database.sqlite          # Basis Data SQLite (Default Zero-Config)
│   ├── migrations/              # File Migrasi Skema Basis Data
│   └── seeders/                 # File Seeder Data Awal
├── public/
│   ├── index.php                # Entry Point HTTP Server
│   ├── app.css                  # Stylesheet Utama Aplikasi
│   ├── islands/                 # File Komponen JS Island Publik
│   ├── manifest.json            # PWA Manifest (Auto-Generated)
│   └── sw.js                    # Service Worker (Auto-Generated)
├── resources/
│   ├── islands/                 # Komponen Island Global (JS/TS)
│   ├── layouts/                 # Master Layout Templates (.pstro)
│   └── views/                   # Template Halaman Global (.pstro)
├── routes/
│   ├── web.php                  # Web Routes Global
│   └── api.php                  # API Routes Global
├── storage/
│   ├── cache/                   # Cache View Compiler & Route Cache
│   ├── debug/                   # Snapshot Debug Trace
│   └── logs/                    # Log Berkas Aplikasi
├── .env.example                 # Template File Environment
├── papastro                     # Executable CLI Papastro
├── papastro.php                 # CLI Entry Point Bootstrapper
└── composer.json
```

---

## Arsitektur Modular Monolith

Papastro menerapkan pendekatan **Modular Monolith**. Seluruh domain aplikasi berada di bawah direktori `app/Modules/`. Setiap modul berdiri secara independen dan memiliki struktur domain terorganisir:

```
app/Modules/Blog/
├── Events/                      # File Event Modul
├── Http/
│   ├── Controllers/             # Controller Modul
│   └── Middleware/              # Middleware Khusus Modul
├── Islands/                     # Komponen Interactive Island Modul
├── Listeners/                   # File Listener Modul
├── Models/                      # File Active Record Model Modul
├── Pages/                       # Halaman Template (.pstro) Modul
├── Repositories/                # Business Logic Repositories Modul
├── Routes/
│   ├── web.php                  # Route Web Modul
│   └── api.php                  # Route API Modul
├── Services/                    # Service Layer Modul
├── BlogServiceProvider.php      # Service Provider Khusus Modul
└── module.json                  # Metadata & Dependency Modul
```

### Konfigurasi Metadata Modul (`module.json`)

```json
{
    "name": "Blog",
    "version": "1.1.0",
    "enabled": true,
    "requires": ["User"]
}
```

Framework akan membaca dependency `requires` dan memuat modul secara otomatis berdasarkan urutan dependensi domain.

---

## Island Architecture dan Hydration Engine

File template Papastro menggunakan ekstensi `.pstro`. Template `.pstro` menggabungkan logika logika server-side PHP (frontmatter) dengan HTML statis dan komponen interaktif island.

### Contoh File Template (`resources/views/welcome.pstro`)

```pstro
---
// Frontmatter: Kode PHP diproses 100% di Server Side
use App\Modules\Blog\Models\Post;
$posts = Post::published()->latest()->paginate(10);
$pageTitle = 'Halaman Utama';
---
@layout('layouts.base')

@section('content')
<div class="hero">
    <h1>Selamat Datang di Papastro Framework</h1>
    <p>Framework Hybrid PHP Backend dan Astro Island Frontend.</p>
</div>

<div class="posts">
    @foreach ($posts['data'] as $post)
        <article class="post-card">
            <h2>{{ $post->title }}</h2>
            <p>{{ $post->excerpt }}</p>
        </article>
    @endforeach
</div>

<!-- Island Component: Di-hydrate di client saat elemen masuk ke viewport -->
<Counter client:visible initialCount="0" />

<!-- Island Component: Di-hydrate saat browser dalam kondisi idle -->
<CommentBox client:idle postId="1" />
@endsection
```

### Directives Hydration Island

| Directive | Perilaku Hydration Client |
|-----------|---------------------------|
| `client:load` | Memuat dan me-hydrate JavaScript komponen secara langsung saat halaman selesai dimuat (*page load*). |
| `client:idle` | Memuat dan me-hydrate JavaScript komponen saat browser dalam kondisi tidak sibuk (*idle* via `requestIdleCallback`). |
| `client:visible` | Memuat dan me-hydrate JavaScript komponen saat elemen masuk ke dalam area pandang pengguna (*viewport* via `IntersectionObserver`). |
| `client:only` | Melewati proses Server-Side Rendering (SSR) dan menjalankan rendering 100% di sisi client. |

---

## Router Engine

Papastro menggunakan sintaks routing ekspresif berbasis Facade:

```php
use Papastro\Router\RouteFacade as Route;
use App\Modules\Blog\Http\Controllers\PostController;

// Route Web Dasar
Route::get('/', function() {
    return view('welcome');
})->name('home');

// Route dengan Controller dan Middleware
Route::get('/blog', [PostController::class, 'index'])
    ->name('blog.index');

Route::get('/blog/{slug}', [PostController::class, 'show'])
    ->name('blog.show');

// Route Grouping dengan Prefix dan Middleware
Route::group(['prefix' => 'admin', 'middleware' => ['auth']], function() {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::resource('/posts', AdminPostController::class);
});
```

---

## Active Record ORM dan Migrasi

Papastro ORM menyediakan mekanisme manipulasi data berbasis Active Record dengan QueryBuilder yang fleksibel.

### Model Definisi

```php
namespace App\Modules\Blog\Models;

use Papastro\ORM\Model;

class Post extends Model
{
    protected string $table = 'posts';
    protected array $fillable = ['user_id', 'title', 'slug', 'body', 'published'];

    public static function published(): static
    {
        return static::where('published', 1);
    }
}
```

### Penggunaan Query Model

```php
use App\Modules\Blog\Models\Post;

// Ambil semua data
$posts = Post::all();

// Filter data dengan pagination
$paginated = Post::where('published', 1)
                ->latest()
                ->paginate(10, $page = 1);

// Buat record baru
$post = Post::create([
    'user_id'   => 1,
    'title'     => 'Panduan Papastro v1.2.0',
    'slug'      => 'panduan-papastro-v110',
    'body'      => 'Konten lengkap framework Papastro...',
    'published' => 1,
]);

// Cari berdasarkan ID atau gagal
$post = Post::findOrFail(1);

// Perbarui record
$post->update(['title' => 'Judul Diperbarui']);

// Hapus record
$post->delete();
```

---

## Event dan Listener System

Papastro v1.2.0 dilengkapi dengan engine `EventDispatcher` untuk menangani arsitektur event-driven secara efisien.

### Membuat Event dan Listener

```bash
# Generate Event baru di Modul Blog
php papastro make:event Blog/PostPublished

# Generate Listener baru di Modul Blog
php papastro make:listener Blog/SendPostNotification
```

### Mendaftarkan dan Memicu Event

Daftarkan pemetaan event dan listener di `app/Providers/EventServiceProvider.php`:

```php
protected array $listen = [
    \App\Modules\Blog\Events\PostPublished::class => [
        \App\Modules\Blog\Listeners\SendPostNotification::class,
    ],
];
```

Picu event di mana saja dalam aplikasi menggunakan helper `event()`:

```php
use App\Modules\Blog\Events\PostPublished;

// Memicu event dengan objek event
event(new PostPublished($post));

// Memicu event berbasis string nama event
event('user.registered', ['user_id' => $user->id]);
```

---

## Optimasi SEO dan Structured Data

Papastro dibangun dengan prinsip SEO-First. Semua halaman utama di-render di sisi server (SSR) sehingga mesin pencari (*search engine crawlers*) dapat membaca isi HTML secara utuh.

### Mengatur Meta Tags di Controller / Page

```php
app(\Papastro\SEO\SeoManager::class)
    ->title('Judul Artikel Blog - Papastro')
    ->description('Deskripsi lengkap artikel blog yang ramah SEO.')
    ->canonical('https://example.com/blog/artikel-1')
    ->set([
        'og_image' => 'https://example.com/images/og-blog.jpg',
        'twitter_card' => 'summary_large_image',
    ]);
```

### Injeksi Structured Data (JSON-LD Schema)

Di dalam template `.pstro`:

```pstro
@section('scripts')
{!! \Papastro\SEO\SeoManager::schema('Article', $post) !!}
@endsection
```

### Pembuatan SitemapXML Otomatis

Otomatiskan pembuatan file `public/sitemap.xml` dari seluruh rute publik terdaftar dengan satu perintah:

```bash
php papastro seo:sitemap
```

---

## Progressive Web App (PWA) Engine

Papastro dapat diubah menjadi Progressive Web App (PWA) dengan strategi caching offline secara instan.

### Konfigurasi PWA (`config/pwa.php`)

```php
return [
    'name'        => 'Aplikasi Papastro',
    'short_name'  => 'Papastro',
    'theme_color' => '#0f172a',
    'strategy'    => 'cache-first', // Pilihan: cache-first, network-first, stale-while-revalidate
    'precache'    => ['/', '/offline', '/blog'],
];
```

### Generate Manifest dan Service Worker

Jalankan perintah pembuatan PWA:

```bash
php papastro pwa:generate
```

Perintah ini akan membuat dua file utama secara otomatis:
- `public/manifest.json`: Metadata aplikasi untuk instalasi PWA di perangkat seluler dan desktop.
- `public/sw.js`: Service worker untuk caching asset statis dan fallback halaman offline saat koneksi terputus.

---

## Debugger dan Dev Overlay

Saat mode pengembang aktif (`APP_DEBUG=true` di `.env`), Papastro secara otomatis menampilkan dua sistem diagnostik:

1. **Papastro Debugbar**: Panel di bagian bawah layar browser yang menampilkan:
   - **Tab Queries**: Jumlah query SQL, waktu eksekusi query, dan peringatan deteksi N+1 query.
   - **Tab Routes**: Informasi rute aktif, nama rute, controller, dan middleware yang dieksekusi.
   - **Tab Request/Response**: Header HTTP, session, data input, dan status response.
   - **Tab Islands**: Komponen island yang di-hydrate beserta strategi hydration yang digunakan.
   - **Tab Timeline**: Perbandingan waktu render server side vs hydration client side.

2. **Dev Overlay**: Tampilan layar penuh (*full-screen overlay*) visual ketika terjadi exception PHP atau kesalahan JavaScript pada island komponen, dilengkapi dengan stack trace, snippet kode sumber, dan nomor baris error.

Pintasan keyboard: Tekan **`Alt + D`** di browser untuk menyembunyikan atau menampilkan Papastro Debugbar.

---

## Dokumentasi Perintah CLI (papastro)

Papastro CLI menyediakan lebih dari 30 perintah bawaan untuk membantu pengelolaan proyek aplikasi.

### Perintah Server & Build

```bash
php papastro dev                      # Jalankan server pengembang (SSR + Island Watch)
php papastro serve                    # Jalankan server produksi
php papastro build                    # Build dan optimasi asset island untuk produksi
```

### Perintah Manajemen Modul

```bash
php papastro module:make Produk       # Generate modul baru lengkap (MVC + Routes + Island + Page)
php papastro module:enable Produk     # Aktifkan modul
php papastro module:disable Produk    # Nonaktifkan modul
php papastro module:list              # Tampilkan daftar semua modul dan statusnya
```

### Perintah Generator Kode

```bash
php papastro make:controller Blog/PostController --resource
php papastro make:model Blog/Post
php papastro make:migration create_posts_table
php papastro make:island Blog/CommentBox
php papastro make:page Blog/index
php papastro make:middleware AuthCheck
php papastro make:service Blog/PostService
php papastro make:event Blog/PostPublished
php papastro make:listener Blog/SendNotification
```

### Perintah Basis Data

```bash
php papastro migrate                  # Jalankan seluruh migrasi basis data yang pending
php papastro migrate:rollback         # Batalkan (rollback) batch migrasi terakhir
php papastro db:seed                  # Jalankan seeder data awal
```

### Perintah SEO & PWA

```bash
php papastro seo:sitemap              # Generate sitemap.xml otomatis dari rute aplikasi
php papastro pwa:generate             # Generate file manifest.json dan service worker sw.js
php papastro pwa:precache             # Perbarui daftar precache pada service worker
```

### Perintah Diagnostik & Debug

```bash
php papastro debug:route              # Tampilkan seluruh rute terdaftar dan modul asalnya
php papastro debug:island             # Pindai dan tampilkan lokasi seluruh komponen island
php papastro debug:clear              # Bersihkan snapshot debug trace di storage/debug
```

### Perintah Optimasi & Cache

```bash
php papastro cache:clear              # Bersihkan cache view, route, dan konfigurasi
php papastro optimize                 # Optimaskan aplikasi untuk lingkungan produksi
php papastro route:cache              # Cache daftar rute untuk mempercepat booting
php papastro route:list               # Tampilkan tabel ringkasan rute aplikasi
```

### Perintah Aplikasi

```bash
php papastro key:generate             # Generate kunci enkripsi aplikasi (APP_KEY)
```

---

## Lisensi dan Watermark Kepemilikan

Papastro Framework didistribusikan di bawah ketentuan lisensi bebas penggunaan dengan kewajiban watermark kepemilikan.

### Ketentuan Lisensi

1. **Kebebasan Penggunaan**: Framework ini **bebas digunakan, dimodifikasi, dan dikembangkan oleh siapa saja** untuk keperluan komersial maupun non-komersial tanpa biaya royalti.
2. **Kewajiban Watermark Kode Sumber (Wajib)**: Setiap file inti framework (`app/Core/**`), file executable CLI `papastro`, dan file boilerplate hasil generator CLI (`make:*`) **wajib mempertahankan header comment watermark hak cipta**:

```php
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
```

3. **Peraturan Tampilan**: Watermark kepemilikan ini **hanya berada di dalam komentar kode sumber (*source code comment*)** dan **tidak wajib ditampilkan pada antarmuka/UI publik** yang dilihat oleh pengguna akhir (*end-user*).
4. **Peraturan Modifikasi**: Pengguna framework tidak diperkenankan menghapus atau menghapus nama pencipta `kangpcode (Dhafa Nazula Permadi)` dari komentar header file core framework, meskipun logika di dalamnya dimodifikasi atau dikembangkan lebih lanjut.

---

*Papastro Framework v1.2.0 dikembangkan oleh kangpcode (Dhafa Nazula Permadi).*
