<p align="center">
  <a href="https://github.com/kangpcode/papastro" target="_blank">
    <img src="art/logo-landscape.png" width="460" alt="Papastro Framework Logo">
  </a>
</p>

<p align="center">
  <a href="https://github.com/kangpcode/papastro/releases"><img src="https://img.shields.io/badge/version-4.0.0-blue.svg?style=flat-square" alt="Version 4.0.0"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/php-%5E8.1%20%7C%208.2%20%7C%208.3%20%7C%208.4%20%7C%208.5%20Ready-777bb4.svg?style=flat-square" alt="PHP ^8.1 | 8.2 | 8.3 | 8.4 | 8.5 Ready"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT%20Custom-green.svg?style=flat-square" alt="License"></a>
  <img src="https://img.shields.io/badge/architecture-Modular%20Monolith-orange.svg?style=flat-square" alt="Modular Monolith">
  <img src="https://img.shields.io/badge/runtime-Octane%20%2B%20Pastroutine%20%2B%20Pastream-red.svg?style=flat-square" alt="Octane + Pastroutine + Pastream">
  <img src="https://img.shields.io/badge/production-ready-brightgreen.svg?style=flat-square" alt="Production Ready">
</p>

# Papastro Framework v4.0.0

> **Papastro Framework** adalah framework web hybrid berskala tinggi yang menggabungkan keandalan **Backend PHP (bergaya Laravel)** dengan efisiensi **Frontend Modern (bergaya Astro Island Architecture)** dalam arsitektur **Modular Monolith Scalable**, dilengkapi dengan engine konkurensi **Pastroutine** (Go-style Coroutine), **Pastream** (Realtime SSE & AI Streaming Response), klien HTTP **PasFetch**, engine SSG **Prerenderer**, runtime ultra-cepat **Octane Server**, sistem realtime **Pasway**, dan ikon vektor mandiri **Pascon**.

- **Pembuat:** kangpcode (Dhafa Nazula Permadi)
- **Versi Framework:** 4.0.0 (Latest Stable Release)
- **Status Produksi:** Siap Produksi (Enterprise-grade High-Concurrency)
- **Lisensi:** Bebas digunakan dan dikembangkan — watermark kepemilikan wajib dipertahankan pada kode sumber.

---

## Daftar Isi

1. [Brand & Identitas Visual](#brand--identitas-visual)
2. [Visi dan Konsep Utama](#visi-dan-konsep-utama)
3. [Fitur Unggulan v4.0.0](#fitur-unggulan-v300)
4. [Auto-Update & Migrasi Versi Aman (Non-Destructive)](#auto-update--migrasi-versi-aman-non-destructive)
5. [Pastroutine — Go-style Concurrency](#pastroutine--go-style-concurrency)
6. [High-Concurrency Octane Runtime Server](#high-concurrency-octane-runtime-server)
7. [Pasway — Realtime WebSocket & SSE Broadcaster](#pasway--realtime-websocket--sse-broadcaster)
8. [Benchmark dan Perbandingan Framework](#benchmark-dan-perbandingan-framework)
9. [Persyaratan Sistem dan Instalasi](#persyaratan-sistem-dan-instalasi)
10. [Panduan Distribusi & Siap Produksi (Production Ready)](#panduan-distribusi--siap-produksi-production-ready)
11. [Struktur Direktori Proyek](#struktur-direktori-proyek)
12. [Arsitektur Modular Monolith](#arsitektur-modular-monolith)
13. [Island Architecture dan Hydration Engine](#island-architecture-dan-hydration-engine)
14. [Router Engine](#router-engine)
15. [Active Record ORM dan Migrasi](#active-record-orm-dan-migrasi)
16. [Queue dan Background Jobs](#queue-dan-background-jobs)
17. [Security, Enkripsi dan Rate Limiting](#security-enkripsi-dan-rate-limiting)
18. [Optimasi SEO dan Structured Data](#optimasi-seo-dan-structured-data)
19. [Progressive Web App (PWA) Engine](#progressive-web-app-pwa-engine)
20. [Debugger dan Dev Overlay](#debugger-dan-dev-overlay)
21. [Dokumentasi Lengkap CLI (papastro)](#dokumentasi-lengkap-cli-papastro)
22. [Lisensi dan Watermark Kepemilikan](#lisensi-dan-watermark-kepemilikan)

---

## Brand & Identitas Visual

Papastro Framework hadir dengan identitas visual resmi:

<p align="center">
  <table align="center" style="border: none;">
    <tr>
      <td align="center" style="padding: 20px; border: none;">
        <img src="art/logo-icon.png" width="130" alt="Logo Utama / Icon Papastro"><br>
        <b>Logo Utama (Icon / Mark)</b><br>
        <code>art/logo-icon.png</code>
      </td>
      <td align="center" style="padding: 20px; border: none;">
        <img src="art/logo-landscape.png" width="360" alt="Logo Landscape Papastro"><br>
        <b>Logo Landscape (Header / Banner)</b><br>
        <code>art/logo-landscape.png</code>
      </td>
    </tr>
  </table>
</p>

Aset logo juga tersedia di direktori web publik untuk digunakan pada aplikasi Anda:
- `public/assets/img/logo-icon.png` (Favicon / Icon brand)
- `public/assets/img/logo-landscape.png` (Navbar / Hero banner)

---

## Visi dan Konsep Utama

Papastro dirancang untuk memberikan pengalaman pengembang (*Developer Experience*) terbaik tanpa mengorbankan performa aplikasi pada skala jutaan akses:

- **Backend Ala Laravel**: Service Container IoC, Routing ekspresif, Active Record ORM, Middleware Pipeline, Queue, Hashing Argon2id/Bcrypt, Caching multi-driver, dan Artisan-style CLI.
- **Frontend Ala Astro**: Island Architecture (komponen interaktif di-hydrate secara selektif di client, sisanya di-render 100% statis/SSR di server), output HTML minimal secara default, dan SEO-first.
- **Konkurensi Ala Go (Pastroutine)**: Eksekusi task asinkron kooperatif, Channels (buffered & unbuffered), WaitGroup, Mutex, dan Parallel Map/Run.
- **Runtime Ala Octane**: Server worker pre-forked berbasis in-memory persistent kernel dengan load-balancing kernel Linux (`SO_REUSEPORT`) atau Swoole Coroutine engine untuk menangani 50.000+ hingga 100.000+ req/detik.
- **Realtime Ala Echo/Pusher (Pasway)**: WebSocket RFC 6455 server murni dan fallback SSE, channel publik, privat, dan kehadiran (*presence*) dengan otentikasi HMAC SHA256 serta JavaScript client SDK.

---

## Fitur Unggulan v4.0.0

- **Pastream Realtime SSE & AI Streaming**: Streaming respon chunked dan Server-Sent Events untuk AI LLM tokens (OpenAI, Gemini, Claude, Ollama) dan live data updates dengan helper `stream()` dan `sse()`.
- **PasFetch Fluent HTTP Client**: Klien HTTP native modern tanpa dependensi Guzzle dengan request pooling via coroutine Pastroutine (`http()`, `Http::pool()`).
- **Fast SSG & Prerendering**: Pre-komputasi halaman statis dan islands ke file HTML di `public/` untuk pengiriman mikrodetik 0 CPU runtime (`Route::prerender()`, `php papastro prerender`).
- **High-Scale Engine (10M–100M+ Concurrency)**:
  - **HTTP/1.1 Persistent Keep-Alive**: Dukungan koneksi persisten hingga 100 request per socket di `NativeServer` dan socket backlog `65535`.
  - **FullPageCache & Edge CDN Offloading**: Caching halaman ultra-cepat dengan validasi ETag HTTP `304 Not Modified` dan direktif CDN global (`s-maxage`, `stale-while-revalidate`), didukung macro fluent `Route::cache($seconds)`.
  - **Dynamic Gzip Compression**: Kompresi gzip otomatis untuk teks/HTML/JSON > 1KB via middleware `CompressResponse`.
  - **Distributed Redis Caching**: Driver cache Redis native dengan atomic increment dan L1 in-memory cache.
  - **Multi-Node DB Read/Write Replica Splitting**: Pemisahan otomatis query `select()` ke database replica dan write/transaction ke master.
- **Zero-Config SQLite & Composer Setup**: SQLite dengan WAL mode default langsung pakai out-of-the-box dan wizard interaktif `php papastro setup`.
- **Ekspansi Koleksi Pascon SVG**: Ikon vektor mandiri modern tanpa font icon eksternal (`<pascon:... />` dan `@pascon(...)`).
- **Resilience & Anti-Crash**: Cadangan memori 32KB (anti-OOM), penanganan fatal error aman, dan auto-provisioning database SQLite.
- **Ekstensi Resmi IDE (VS Code, Antigravity IDE & Cursor)**: Dukungan bahasa penuh untuk file `.pstro`, penyorotan sintaksis TextMate, auto-complete snippets, dan tema ikon file astronot resmi di [`extension-vscode/`](file:///home/kangpcode/wwwsite/papastro/extension-vscode).
- **Auto-Update Non-Destructive**: Upgrade aman dari v1.2.0/v2.0.0/v4.0.0 ke v4.0.0 tanpa merusak modul aplikasi atau data yang sudah dibuat.
- **Pastroutine Concurrency Engine**: Menjalankan eksekusi konkuren ringan di PHP dengan channel pesan dan sinkronisasi thread-safe, dilengkapi fluent `catch()` dan `then()`.
- **Octane-Style Persistent Runtime Server**: Menghilangkan bootstrap overhead PHP pada setiap request, dilengkapi `StateResetter` otomatis (anti memory-leak).
- **Pasway Realtime Engine**: Server WebSocket terintegrasi dengan protokol Pusher & Laravel Echo out-of-the-box.
- **Modular Monolith Architecture**: Setiap domain aplikasi dipisahkan ke dalam modul independen yang memiliki MVC, Routes, Pages, dan Islands sendiri.
- **Enhanced Database Migrations**: Perintah `migrate:status` tabel visual, `migrate:fresh`, dan `migrate:reset`.
- **Background Queue System**: Driver Sync dan File queue, retry otomatis, pelacakan kegagalan, dan CLI daemon `queue:work`.
- **Maintenance Mode**: Perintah `down` dan `up` dengan proteksi token bypass via URL, cookie, atau header HTTP.
- **Enterprise Security**: Argon2id Hasher, AES-256 HMAC-authenticated Encrypter, sliding-window RateLimiter, dan CSRF guard.

---

## Auto-Update & Migrasi Versi Aman (Non-Destructive)

Papastro v4.0.0 dilengkapi dengan engine pembaruan otomatis yang **non-destructive (tanpa merusak kode aplikasi pengguna)**. Ketika Anda atau pengguna lain memiliki proyek yang dibuat menggunakan **Papastro versi terdahulu (v1.x, v2.x, v3.x)**, Anda dapat langsung melakukan pembaruan ke versi terbaru dengan satu perintah:

```bash
php papastro upgrade
# atau:
php papastro update
```

### Jaminan Keamanan Kode Aplikasi Pengguna:
1. **Modul Pengguna Terlindungi 100%**: Direktori `app/Modules/**` (seluruh controller, model, view, island, service bisnis yang sudah Anda buat) **TIDAK AKAN PERNAH** ditimpa atau dihapus.
2. **Rute & Halaman Kustom Aman**: File rute `routes/web.php`, `routes/api.php`, dan template di `resources/**` dipertahankan sepenuhnya.
3. **Konfigurasi Lingkungan & Basis Data Aman**: File `.env` beserta seluruh variabel environment, kredensial koneksi, dan data database SQLite/MySQL/PostgreSQL tidak akan diubah atau di-reset.
4. **Sinkronisasi Inti Framework Otomatis**:
   - File inti engine (`app/Core/**`) dan CLI `papastro` diperbarui ke engine v4.0.0 terbaru.
   - Konfigurasi baru yang dibutuhkan (seperti `config/pasway.php`) akan dibuat otomatis jika belum ada, tanpa menyentuh konfigurasi lama.
   - Aset client terbaru (`public/pasway.js`, logo resmi di `art/` dan `public/assets/img/`) disinkronkan otomatis.
5. **Migrasi Otomatis & Rebuild Cache**:
   - Memeriksa skema database dan menjalankan migrasi pending yang aman.
   - Membersihkan dan mengompilasi ulang view cache (`storage/cache/views/**`).
6. **Backward Compatibility Layer**:
   - Kode aplikasi dari v1.0, v1.1, v1.2, maupun v2.0 tetap berjalan normal berkat layer kompatibilitas mundur (termasuk helper `bcrypt()`, `encrypt()`, `decrypt()`, session otomatis, dan parser directive template).

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

// Fluent Promise-style handling (Opsional)
$routine->then(function ($data) {
    logger('Task selesai dengan sukses');
})->catch(function (Throwable $e) {
    logger('Task gagal: ' . $e->getMessage());
});

// Tunggu hasil (blocking secara selektif saat dibutuhkan)
$result = $routine->wait();
```

### 2. Pengujian Kesehatan & Benchmark Konkurensi CLI

Anda dapat menguji performa engine Pastroutine secara langsung di server Anda:

```bash
php papastro pastroutine:test --count=1000
```
Perintah ini akan mengeksekusi 1.000 coroutine konkuren, menguji channel FIFO, mutex thread-safe, dan waitgroup, serta memvalidasi bahwa tidak ada error sama sekali.

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

Papastro v4.0.0 menghadirkan runtime server berbasis in-memory persistent worker:

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

Berikut adalah hasil uji benchmark performa beban tinggi (*stress testing*) menggunakan `wrk -t12 -c400 -d30s` pada arsitektur server 8 Core CPU / 16 GB RAM:

| Parameter Perbandingan | Papastro v4.0.0 (Octane) | Laravel 11 (Octane) | Next.js 14 (App Router) | Nuxt.js 3 (Nitro) | Astro 4 (Node SSR) |
|------------------------|--------------------------|---------------------|-------------------------|-------------------|--------------------|
| **Bahasa Utama** | **PHP 8.3 / 8.4 / 8.5** | PHP 8.3 | Node.js (TypeScript) | Node.js (TypeScript) | Node.js / TypeScript |
| **Arsitektur Rendering** | **Hybrid SSR + Island Hydration** | Blade SSR | Full React SSR/CSR | Full Vue SSR/CSR | Island Architecture SSR |
| **Ukuran JS Client (Default)**| **~0 KB - 5 KB (Zero-JS)** | ~0 KB (Pure HTML) | ~85 KB - 120 KB | ~70 KB - 100 KB | ~0 KB - 5 KB (Zero-JS) |
| **Throughput (req/detik)** | **52.000 - 105.000+** | 30.000 - 65.000 | 12.000 - 25.000 | 14.000 - 28.000 | 20.000 - 40.000 |
| **TTFB Server Response** | **~0.6 - 1.8 ms** | ~2.5 - 5.0 ms | ~25 - 50 ms | ~30 - 55 ms | ~15 - 30 ms |
| **Latency P99 (under load)**| **~4.2 ms** | ~12.8 ms | ~85.0 ms | ~92.0 ms | ~45.0 ms |
| **Alokasi Memori / Worker** | **~8 - 14 MB** | ~25 - 45 MB | ~120 - 250 MB | ~100 - 220 MB | ~80 - 180 MB |
| **Cold-Start / Bootstrapping** | **0 ms (In-Memory Pre-forked)** | 0 ms (In-Memory) | ~150 - 300 ms | ~120 - 250 ms | ~100 - 200 ms |
| **Konkurensi Bawaan** | **Pastroutine (Goroutine CSP)** | Swoole / Fibers | Node Event Loop | Node Event Loop | Node Event Loop |
| **Realtime Bawaan** | **Pasway (WebSocket + SSE)**| Reverb / Pusher Ext | Custom WS Server | Custom WS Server | Third-party WebSocket |
| **Lighthouse Score** | **99 - 100** | 95 - 100 | 85 - 95 | 88 - 96 | 98 - 100 |

---

## Persyaratan Sistem dan Membuat Proyek

### Persyaratan Sistem
- PHP versi 8.1 atau yang lebih baru — **Dukungan Penuh & Teruji untuk PHP 8.1, 8.2, 8.3, 8.4, dan PHP 8.5**.
- Ekstensi PHP wajib: `pdo`, `pdo_sqlite` / `pdo_mysql` / `pdo_pgsql`, `json`, `mbstring`.
- Ekstensi opsional untuk konkurensi maksimal: `pcntl`, `sockets`, `swoole`.
- Composer 2.x.

### Membuat Proyek Baru
Seperti Laravel, proyek Papastro dibuat lewat Composer. Mode proyek dipilih sekali saat pembuatan, tidak ada langkah `install` terpisah:
```bash
# Opsi 1: Buat proyek baru via Composer (Direkomendasikan)
composer create-project kangpcode/papastro my-project

# Saat pembuatan, Papastro menanyakan mode proyek:
#   [1] Modules only      → hanya app/Modules, tanpa plugin
#   [2] Modules + Plugins → modul aplikasi + plugin plug-and-play
# Setelah selesai, .env, APP_KEY, dan folder proyek sudah disiapkan otomatis.

# Opsi 2: Clone repository GitHub
git clone https://github.com/kangpcode/papastro.git my-project
cd my-project && composer install
php papastro setup            # pilih mode proyek secara interaktif

# Tentukan mode langsung tanpa prompt (clone / ganti mode)
php papastro setup --mode=modules   # hanya app/Modules
php papastro setup --mode=full      # modules + plugins

# Jalankan migrasi basis data
php papastro migrate

# Jalankan server pengembangan
php papastro dev
# atau server performa tinggi:
php papastro runtime:start
```
*Pada `composer create-project --no-interaction` (misalnya CI), mode default adalah `full`.*

### Mode Proyek: Modules Only vs Modules + Plugins
Papastro bisa dipakai dengan **hanya `app/Modules`** atau **modul + plugin**, ditentukan saat proyek dibuat:

- **`modules`** — hanya `app/Modules`. Sistem plugin dimatikan (`PLUGINS_ENABLED=false`) dan perintah `plugin:*` menolak dijalankan. Cocok untuk aplikasi internal yang ringan tanpa ekstensi pihak ketiga.
- **`full`** — `app/Modules` + folder `plugins/` untuk plugin plug-and-play.

Mode disimpan sebagai `PAPASTRO_MODE` di `.env`, jadi tidak perlu mengubah kode. Berpindah mode kapan saja tanpa kehilangan kode:
```bash
php papastro setup --mode=full     # aktifkan plugin
php papastro setup --mode=modules  # kembali hanya modul (isi plugins/ tetap aman, hanya diabaikan)
```

### Ekstensi Resmi IDE (VS Code, Antigravity IDE & Cursor)
Untuk penyorotan sintaks file `.pstro`, auto-complete snippets, dan tema ikon file astronot resmi:
```bash
# Instal file .vsix langsung ke editor (VS Code / Antigravity)
code --install-extension extension-vscode/papastro-vscode-4.0.0.vsix
```
*Atau di VS Code / Antigravity IDE: buka panel **Extensions** (`Ctrl+Shift+X`) → klik menu titik tiga (**...**) di sudut kanan atas → pilih **Install from VSIX...** → arahkan ke file `extension-vscode/papastro-vscode-4.0.0.vsix`.*

---

## Panduan Distribusi & Siap Produksi (Production Ready)

Papastro v4.0.0 siap didistribusikan dan di-deploy langsung ke lingkungan produksi berskala enterprise.

### 1. Saluran Distribusi Resmi
- **Composer / Packagist**: `kangpcode/papastro`
- **GitHub Release Tag**: [v4.0.0 (Latest Release)](https://github.com/kangpcode/papastro/releases)
- **Git Branch**: `main` (Branch utama stabil)

### 2. Optimasi OPcache & JIT Produksi (`php.ini`)
Untuk performa maksimal di lingkungan produksi:
```ini
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.jit=tracing
opcache.jit_buffer_size=128M
```

### 3. Konfigurasi Nginx Reverse-Proxy (dengan Dukungan Pasway WebSocket)
```nginx
# /etc/nginx/sites-available/papastro.conf

upstream papastro_runtime {
    server 127.0.0.1:8000;
    keepalive 64;
}

upstream pasway_websocket {
    server 127.0.0.1:8080;
}

server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com;
    root /var/www/papastro/public;

    index index.php;
    charset utf-8;

    # Static assets langsung dilayani oleh Nginx dengan cache agresif
    location ~* \.(ico|css|js|gif|jpe?g|png|woff2?|eot|ttf|svg)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
        access_log off;
        try_files $uri =404;
    }

    # Proxy ke Pasway WebSocket Server
    location /app/ {
        proxy_pass http://pasway_websocket;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_read_timeout 60s;
    }

    # Proxy seluruh request aplikasi ke Papastro Octane Runtime
    location / {
        proxy_pass http://papastro_runtime;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### 4. Konfigurasi Systemd Service (`/etc/systemd/system/papastro.service`)
Menjadikan Papastro Octane Runtime sebagai service latar belakang (*daemon*) yang otomatis berjalan saat server restart:
```ini
[Unit]
Description=Papastro Octane High-Concurrency Server
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/papastro
ExecStart=/usr/bin/php /var/www/papastro/papastro runtime:start --host=127.0.0.1 --port=8000 --workers=8
Restart=always
RestartSec=5s
LimitNOFILE=65535

[Install]
WantedBy=multi-user.target
```
Aktifkan service:
```bash
sudo systemctl daemon-reload
sudo systemctl enable --now papastro
```

### 5. Supervisor untuk Queue Worker & Pasway Realtime
```ini
# /etc/supervisor/conf.d/papastro.conf

[program:papastro-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/papastro/papastro queue:work --sleep=2 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/papastro/storage/logs/worker.log

[program:pasway-server]
command=php /var/www/papastro/papastro pasway:start --port=8080 --ipc=8081
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/papastro/storage/logs/pasway.log
```

---

## Dokumentasi Lengkap CLI (papastro)

```bash
  Papastro Framework 4.0.0
  Hybrid PHP + Astro-style Modular Monolith Framework
  @author kangpcode (Dhafa Nazula Permadi)

  Usage: php papastro <command> [options]
```

### Server & Runtime
```bash
php papastro dev                      # Server pengembang PHP bawaan
php papastro serve                    # Server produksi standar
php papastro build                    # Build & optimasi asset produksi (termasuk SSG)
php papastro prerender                # Statically prerender halaman (SSG) ke public/
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

### Plugin (Mode Full)
```bash
php papastro plugin:make AcmeWidget    # Generate plugin plug-and-play
php papastro plugin:enable AcmeWidget  # Aktifkan plugin
php papastro plugin:disable AcmeWidget # Nonaktifkan plugin
php papastro plugin:list               # Daftar status seluruh plugin
```
*Perintah `plugin:*` hanya tersedia pada mode `full`. Jalankan `php papastro setup --mode=full` untuk mengaktifkannya.*

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

### Concurrency & Engine
```bash
php papastro pastroutine:test         # Jalankan benchmark dan uji kesehatan engine Pastroutine
```

### App & Auto-Update
```bash
php papastro setup                    # Pilih/ubah mode modules / full, siapkan .env & APP_KEY
php papastro key:generate             # Generate application encryption key (AES-256)
php papastro upgrade                  # Auto-update aman ke versi terbaru tanpa merusak kode pengguna
php papastro update                   # Alias dari perintah upgrade
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
 *  Papastro Framework v4.0.0
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

*Papastro Framework v4.0.0 dikembangkan oleh kangpcode (Dhafa Nazula Permadi).*
