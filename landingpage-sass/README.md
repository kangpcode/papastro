# Papastro Framework Official Landing Page & Documentation (GitHub Pages)

Website resmi dan portal dokumentasi lengkap untuk **Papastro Framework v5.0.0 Stable**, dirancang khusus untuk di-hosting di **GitHub Pages**.

> **Manifesto:** *"PHP Tidak Akan Musnah Oleh Jaman (PHP Melawan) — PHP Strikes Back!"*  
> **Author & Creator:** [kangpcode (Dhafa Nazula Permadi)](https://github.com/kangpcode)  
> **Lisensi:** MIT License  

---

## 🚀 Fitur Website
1. **GitHub Pages Ready:**
   - Termasuk file `.nojekyll` untuk mencegah Jekyll mengabaikan aset statis.
   - Kompatibel dengan GitHub Actions Deployment (`actions/deploy-pages@v4`).
2. **SASS / SCSS Modular Architecture:**
   - Seluruh style ditulis menggunakan SASS modern (`scss/` directory) dengan tokens variabel, dark glassmorphism, responsive grid, dan tipografi modern.
   - File hasil kompilasi tersedia langsung di `css/style.css`.
3. **Dokumentasi Interaktif Terintegrasi:**
   - Navigasi tab untuk Panduan Mulai, Routing, Komponen Astro Islands (`.pstro`), Multi-DB ORM (MySQL, Postgres, Supabase, Mongo, SQLite), PasRPC, Pastroutine, Pasway WebSockets, DDoS Shield, dan Deployment.
4. **Benchmark Nyata & Terverifikasi:**
   - Menampilkan visualisasi animasi SVG benchmark nyata melawan Go Fiber, Goravel, Laravel Octane, Symfony FrankenPHP, CodeIgniter 4, dan Yii.
5. **Kanal Donasi & Sponsorship:**
   - Tombol donasi terintegrasi: Saweria, Trakteer.id, GitHub Sponsors, PayPal, Wallet Crypto USDT (BEP20/TRC20), dan Transfer Bank dengan fitur copy address + toast notification.
6. **Profil Developer & Kepemilikan Resmi:**
   - Deklarasi hak kekayaan intelektual resmi atas ekstensi `.pstro` dan framework oleh **kangpcode (Dhafa Nazula Permadi)**.

---

## 🛠️ Pengembangan Lokal

### 1. Menjalankan Website Secara Lokal
Anda dapat menggunakan built-in PHP web server atau browser live server sederhana:

```bash
# Menggunakan PHP built-in server
php -S 127.0.0.1:3000 -t .

# Buka http://127.0.0.1:3000 di browser
```

### 2. Mengompilasi Perubahan SASS
Jika Anda ingin mengubah style di folder `scss/`:

```bash
# Install dependencies
npm install

# Kompilasi sekali (Compressed)
npm run build:css

# Atau jalankan mode watch saat sedang coding
npm run watch:css
```

---

## 🌐 Cara Publikasi ke GitHub Pages

### Opsi A: Otomatis via GitHub Actions (Direkomendasikan)
File workflow telah disiapkan di `.github/workflows/deploy-pages.yml`.
1. Masuk ke **Settings** repository GitHub Anda: `https://github.com/kangpcode/papastro/settings/pages`
2. Di bagian **Build and deployment > Source**, pilih **GitHub Actions**.
3. Setiap kali Anda melakukan push ke branch `main`, GitHub Actions akan otomatis mengompilasi SASS dan mempublikasikan folder ini ke URL GitHub Pages Anda:
   `https://kangpcode.github.io/papastro/`

### Opsi B: Menggunakan Branch `gh-pages`
Jika ingin menggunakan branch khusus:
```bash
git subtree push --prefix landingpage-sass origin gh-pages
```

---

## 📄 Hak Cipta
Hak Cipta © 2026 **kangpcode (Dhafa Nazula Permadi)**. Seluruh hak cipta dilindungi undang-undang di bawah ketentuan **MIT License**.
