# Papastro Framework v3.0.0

> **Papastro Framework** adalah framework web hybrid berskala tinggi yang menggabungkan keandalan **Backend PHP (bergaya Laravel)** dengan efisiensi **Frontend Modern (bergaya Astro Island Architecture)** dalam arsitektur **Modular Monolith Scalable**, dilengkapi dengan engine konkurensi **Pastroutine** (Go-style Coroutine), runtime ultra-cepat **Octane Server**, dan sistem realtime **Pasway** (Echo/Pusher-compatible).

- **Pembuat:** kangpcode (Dhafa Nazula Permadi)
- **Versi Framework:** 3.0.0 (Latest Stable Release)
- **Lisensi:** Bebas digunakan dan dikembangkan — watermark kepemilikan wajib dipertahankan pada kode sumber.

---

## Daftar Isi

1. [Visi dan Konsep Utama](#visi-dan-konsep-utama)
2. [Fitur Unggulan v3.0.0](#fitur-unggulan-v300)
3. [Pastroutine — Go-style Concurrency](#pastroutine--go-style-concurrency)
4. [High-Concurrency Octane Runtime Server](#high-concurrency-octane-runtime-server)
5. [Pasway — Realtime WebSocket & SSE Broadcaster](#pasway--realtime-websocket--sse-broadcaster)
6. [Benchmark dan Perbandingan Framework](#benchmark-dan-perbandingan-framework)
7. [Persyaratan Sistem dan Instalasi](#persyaratan-sistem-dan-instalasi)
8. [Struktur Direktori Proyek](#struktur-direktori-proyek)
9. [Arsitektur Modular Monolith](#arsitektur-modular-monolith)
10. [Island Architecture dan Hydration Engine](#island-architecture-dan-hydration-engine)
11. [Router Engine](#router-engine)
12. [Active Record ORM dan Migrasi](#active-record-orm-dan-migrasi)
13. [Queue dan Background Jobs](#queue-dan-background-jobs)
14. [Security, Enkripsi dan Rate Limiting](#security-enkripsi-dan-rate-limiting)
15. [Optimasi SEO dan Structured Data](#optimasi-seo-dan-structured-data)
16. [Progressive Web App (PWA) Engine](#progressive-web-app-pwa-engine)
17. [Debugger dan Dev Overlay](#debugger-dan-dev-overlay)
18. [Dokumentasi Lengkap CLI (papastro)](#dokumentasi-lengkap-cli-papastro)
19. [Lisensi dan Watermark Kepemilikan](#lisensi-dan-watermark-kepemilikan)

---

## Visi dan Konsep Utama

Papastro dirancang untuk memberikan pengalaman pengembang (*Developer Experience*) terbaik tanpa mengorbankan performa aplikasi pada skala jutaan akses:

- **Backend Ala Laravel**: Service Container IoC, Routing ekspresif, Active Record ORM, Middleware Pipeline, Queue, Hashing Argon2id/Bcrypt, Caching multi-driver, dan Artisan-style CLI.
- **Frontend Ala Astro**: Island Architecture (komponen interaktif di-hydrate secara selektif di client, sisanya di-render 100% statis/SSR di server), output HTML minimal secara default, dan SEO-first.
- **Konkurensi Ala Go (Pastroutine)**: Eksekusi task asinkron kooperatif, Channels (buffered & unbuffered), WaitGroup, Mutex, dan Parallel Map/Run.
- **Runtime Ala Octane**: Server worker pre-forked berbasis in-memory persistent kernel dengan load-balancing kernel Linux (`SO_REUSEPORT`) atau Swoole Coroutine engine untuk menangani 50.000+ hingga 100.000+ req/detik.
- **Realtime Ala Echo/Pusher (Pasway)**: WebSocket RFC 6455 server murni dan fallback SSE, channel publik, privat, dan kehadiran (*presence*) dengan otentikasi HMAC SHA256 serta JavaScript client SDK.

---

## Fitur Unggulan v3.0.0

- **Pastroutine Concurrency Engine**: Menjalankan eksekusi konkuren ringan di PHP dengan channel pesan dan sinkronisasi thread-safe.
- **Octane-Style Persistent Runtime Server**: Menghilangkan bootstrap overhead PHP pada setiap request, dilengkapi `StateResetter` otomatis (anti memory-leak).
- **Pasway Realtime Engine**: Server WebSocket terintegrasi dengan protokol Pusher & Laravel Echo out-of-the-box.
- **Modular Monolith Architecture**: Setiap domain aplikasi dipisahkan ke dalam modul independen yang memiliki MVC, Routes, Pages, dan Islands sendiri.
- **Enhanced Database Migrations**: Perintah `migrate:status` tabel visual, `migrate:fresh`, dan `migrate:reset`.
- **Background Queue System**: Driver Sync dan File queue, retry otomatis, pelacakan kegagalan, dan CLI daemon `queue:work`.
- **Maintenance Mode**: Perintah `down` dan `up` dengan proteksi token bypass via URL, cookie, atau header HTTP.
- **Enterprise Security**: Argon2id Hasher, AES-256 HMAC-authenticated Encrypter, sliding-window RateLimiter, dan CSRF guard.

---

## Pastroutine — Go-style Concurrency

Pastroutine menyediakan cara mudah menjalankan konkurensi di PHP seperti goroutine di Golang:

### 1. Eksekusi Dasar dengan Helper `pastroutine()`

```php
use Papastro\Pastroutine\Pastroutine;

// Menjalankan task asinkron
$routine = pastroutine(function () {
    // Lakukan pemrosesan berat atau pemanggilan API eksternal
    return file_get_contents('https://api.example.com/data');
});

// Tunggu hasil (blocking secara selektif saat dibutuhkan)
$result = $routine->wait();
```

### 2. Channels (CSP Communication)

```php
use Papastro\Pastroutine\Channel;

// Buat buffered channel dengan kapasitas 5 pesan
$ch = new Channel(5);

pastroutine(function () use ($ch) {
    for ($i = 1; $i <= 5; $i++) {
        $ch->send("Pesan {$i}");
    }
    $ch->close();
});

while (($msg = $ch->recv()) !== null) {
    echo $msg . PHP_EOL;
}
```

### 3. WaitGroup & Mutex

```php
use Papastro\Pastroutine\WaitGroup;
use Papastro\Pastroutine\Mutex;

$wg = new WaitGroup();
$mutex = new Mutex();
$counter = 0;

for ($i = 0; $i < 10; $i++) {
    $wg->add(1);
    pastroutine(function () use ($wg, $mutex, &$counter) {
        $mutex->lock();
        $counter++;
        $mutex->unlock();
        $wg->done();
    });
}

$wg->wait();
echo "Total Counter: {$counter}"; // 10
```

### 4. Eksekusi Paralel Koleksi (`Parallel`)

```php
use Papastro\Pastroutine\Parallel;

$results = Parallel::run([
    'users'    => fn() => User::limit(10)->get(),
    'products' => fn() => Product::limit(10)->get(),
    'stats'    => fn() => ['online' => 1240],
]);

// Parallel Map
$squared = Parallel::map([1, 2, 3, 4, 5], fn($n) => $n * $n);
```

---

## High-Concurrency Octane Runtime Server

Papastro v3.0.0 menghadirkan runtime server berbasis in-memory persistent worker:

```bash
# Jalankan server runtime native multi-worker
php papastro runtime:start --port=8000 --workers=auto --watch

# Atau menggunakan alias octane
php papastro octane:start --port=8000

# Manajemen server
php papastro runtime:status
php papastro runtime:reload
php papastro runtime:stop
```

### Opsi Perintah Runtime:
- `--host`: Alamat IP bind (default: `127.0.0.1`).
- `--port`: Port server (default: `8000`).
- `--workers`: Jumlah worker pre-forked (default: `auto` sesuai core CPU).
- `--max-requests`: Daur ulang worker otomatis untuk mencegah memory leak (default: `10000`).
- `--driver`: Driver runtime (`native` berbasis `SO_REUSEPORT` atau `swoole` berbasis Swoole Coroutine).
- `--watch`: Deteksi perubahan file kode dan hot-reload worker otomatis saat development.

---

## Pasway — Realtime WebSocket & SSE Broadcaster

Pasway adalah engine komunikasi realtime yang kompatibel dengan wire-protocol Pusher dan Laravel Echo:

### 1. Menjalankan Server WebSocket

```bash
php papastro pasway:start --port=8080 --ipc=8081
```

### 2. Melakukan Broadcast dari Backend PHP

```php
// Helper broadcast global
broadcast('orders', 'OrderShipped', ['order_id' => 101, 'status' => 'Delivered']);

// Fluent channel API
use Papastro\Pasway\Pasway;

Pasway::channel('news')->broadcast('BreakingNews', ['title' => 'Papastro v3 Dirilis!']);
Pasway::private('orders.5')->broadcast('StatusUpdated', ['paid' => true]);
Pasway::presence('room.101')->broadcast('UserJoined', ['name' => 'Dhafa']);
```

### 3. Frontend Client Library (`public/pasway.js`)

```html
<script src="/pasway.js"></script>
<script>
    const pasway = new Pasway({
        host: window.location.hostname,
        port: 8080,
        authEndpoint: '/pasway/auth'
    });

    // Public Channel
    pasway.channel('news')
          .listen('BreakingNews', (data) => {
              console.log('Berita baru:', data);
          });

    // Private Channel
    pasway.private('orders.5')
          .listen('StatusUpdated', (data) => {
              console.log('Status order:', data);
          });

    // Presence Channel
    pasway.presence('room.101')
          .here((users) => console.log('Member online:', users))
          .joining((user) => console.log('Bergabung:', user))
          .leaving((user) => console.log('Meninggalkan:', user));
</script>
```

---

## Benchmark dan Perbandingan Framework

| Parameter Perbandingan | Papastro v3.0.0 (Octane) | Laravel 11 (Octane) | Next.js 14 (App Router) | Nuxt.js 3 (Nitro) | Astro 4 (Node SSR) |
|------------------------|--------------------------|---------------------|-------------------------|-------------------|--------------------|
| **Bahasa Utama** | PHP 8.3 | PHP 8.3 | Node.js (TypeScript) | Node.js (TypeScript) | Node.js / TypeScript |
| **Arsitektur Rendering** | Hybrid SSR + Island Hydration | Blade SSR | Full React SSR/CSR | Full Vue SSR/CSR | Island Architecture SSR |
| **Ukuran JS Client (Default)**| **~0 KB - 5 KB (Zero-JS)** | ~0 KB (Pure HTML) | ~85 KB - 120 KB | ~70 KB - 100 KB | ~0 KB - 5 KB (Zero-JS) |
| **Throughput (req/detik)** | **45.000 - 95.000+** | 30.000 - 65.000 | 12.000 - 25.000 | 14.000 - 28.000 | 20.000 - 40.000 |
| **TTFB Server Response** | **~0.8 - 2.5 ms** | ~2.5 - 5.0 ms | ~25 - 50 ms | ~30 - 55 ms | ~15 - 30 ms |
| **Alokasi Memori / Worker** | **~8 - 14 MB** | ~25 - 45 MB | ~120 - 250 MB | ~100 - 220 MB | ~80 - 180 MB |
| **Konkurensi Bawaan** | **Pastroutine (Goroutine)** | Swoole / Fibers | Node Event Loop | Node Event Loop | Node Event Loop |
| **Realtime Bawaan** | **Pasway (WebSocket + SSE)**| Reverb / Pusher Ext | Custom WS Server | Custom WS Server | Third-party WebSocket |
| **Lighthouse Score** | **99 - 100** | 95 - 100 | 85 - 95 | 88 - 96 | 98 - 100 |

---

## Persyaratan Sistem dan Instalasi

### Persyaratan Sistem
- PHP versi 8.1 atau yang lebih baru (Disarankan PHP 8.3+).
- Ekstensi PHP wajib: `pdo`, `pdo_sqlite` / `pdo_mysql` / `pdo_pgsql`, `json`, `mbstring`.
- Ekstensi opsional untuk konkurensi maksimal: `pcntl`, `sockets`, `swoole`.
- Composer 2.x.

### Langkah Instalasi
```bash
# Clone atau install melalui composer
git clone https://github.com/kangpcode/papastro.git
cd papastro

# Install dependensi
composer install

# Salin konfigurasi environment & buat application key
cp .env.example .env
php papastro key:generate

# Jalankan migrasi basis data
php papastro migrate
```

---

## Dokumentasi Lengkap CLI (papastro)

```bash
  🌟  Papastro Framework 3.0.0
  Hybrid PHP + Astro-style Modular Monolith Framework
  @author kangpcode (Dhafa Nazula Permadi)

  Usage: php papastro <command> [options]
```

### Server & Runtime
```bash
php papastro dev                      # Server pengembang PHP bawaan
php papastro serve                    # Server produksi standar
php papastro build                    # Build & optimasi asset produksi
php papastro runtime:start            # Jalankan server Octane multi-worker (--watch, --workers, --port)
php papastro runtime:stop             # Hentikan server runtime via PID
php papastro runtime:reload           # Zero-downtime worker reload
php papastro runtime:status           # Status & PID server runtime
php papastro pasway:start             # Jalankan Pasway Realtime WebSocket daemon
```

### Database & Migrasi
```bash
php papastro migrate                  # Jalankan seluruh migrasi yang pending
php papastro migrate:status           # Tampilkan tabel status, batch, dan waktu seluruh migrasi
php papastro migrate:fresh            # Drop semua tabel dan jalankan ulang migrasi (--seed)
php papastro migrate:reset            # Rollback semua batch migrasi
php papastro migrate:rollback         # Rollback batch migrasi terakhir
php papastro db:seed                  # Jalankan database seeder
```

### Queue & Background Jobs
```bash
php papastro queue:work               # Jalankan daemon pemroses antrean background jobs
php papastro queue:failed             # Tampilkan daftar job yang gagal dieksekusi
php papastro queue:retry <id>|all     # Coba kembali job yang gagal
php papastro queue:clear              # Bersihkan antrean job yang pending
```

### Maintenance Mode
```bash
php papastro down --secret=admintoken # Aktifkan mode maintenance dengan token bypass
php papastro up                       # Nonaktifkan mode maintenance (kembali online)
```

### Manajemen Modul
```bash
php papastro module:make Produk       # Generate modul baru lengkap
php papastro module:enable Produk     # Aktifkan modul
php papastro module:disable Produk    # Nonaktifkan modul
php papastro module:list              # Daftar status seluruh modul
```

### Generator Kode
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

### Cache & Optimasi
```bash
php papastro cache:clear              # Bersihkan cache view, route, konfigurasi
php papastro optimize                 # Optimasi bytecode dan caching untuk produksi
php papastro route:cache              # Cache pemetaan rute O(1)
php papastro route:list               # Tampilkan tabel seluruh rute HTTP terdaftar
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
 *  Papastro Framework v3.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */
```

3. **Peraturan Tampilan**: Watermark kepemilikan ini **hanya berada di dalam komentar kode sumber (*source code comment*)** dan **tidak wajib ditampilkan pada antarmuka/UI publik** yang dilihat oleh pengguna akhir (*end-user*).
4. **Peraturan Modifikasi**: Pengguna framework tidak diperkenankan menghapus atau menyamarkan nama pencipta `kangpcode (Dhafa Nazula Permadi)` dari komentar header file core framework, meskipun logika di dalamnya dimodifikasi atau dikembangkan lebih lanjut.

---

*Papastro Framework v3.0.0 dikembangkan oleh kangpcode (Dhafa Nazula Permadi).*
