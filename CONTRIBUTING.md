# Panduan Kontribusi — Papastro Framework

Terima kasih atas ketertarikan Anda untuk berkontribusi pada pengembangan **Papastro Framework**!

- **Pembuat / Maintainer:** kangpcode (Dhafa Nazula Permadi)
- **Email:** dhafanavy@gmail.com
- **Repository:** [https://github.com/kangpcode/papastro](https://github.com/kangpcode/papastro)
- **Versi:** 3.0.0

---

## 1. Prinsip Utama Kontribusi

1. **Mempertahankan Header & Watermark Kepemilikan**:
   Setiap penambahan atau modifikasi file di `app/Core/**` atau generator CLI wajib mempertahankan header watermark:
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
2. **Backward Compatibility**:
   Pembaruan atau fitur baru harus mempertahankan kompatibilitas mundur sehingga aplikasi yang menggunakan Papastro versi lama (v1.2.0 atau di bawahnya) dapat terus berjalan tanpa modifikasi breaking changes.
3. **Standar Kode**:
   - Mematuhi standar PSR-12 dan deklarasi tipe ketat (`declare(strict_types=1);`).
   - Clean architecture, zero external runtime bloat (menjaga dependensi runtime tetap minimal dan ringan).

---

## 2. Alur Kerja Kontribusi (Pull Request)

1. **Fork Repository** ke akun GitHub Anda.
2. Buat branch baru dari branch `main`:
   ```bash
   git checkout -b feat/nama-fitur-baru
   ```
3. Lakukan perubahan kode dan sertakan unit test / feature test di folder `tests/`.
4. Jalankan pengujian menyeluruh:
   ```bash
   php vendor/bin/phpunit
   php papastro pastroutine:test
   ```
5. Commit perubahan dengan pesan commit deskriptif (mengikuti Semantic Commit Messages: `feat:`, `fix:`, `docs:`, `chore:`).
6. Push ke branch forked Anda dan buka **Pull Request** ke branch `main` di repository utama.

---

## 3. Menjalankan Test Suite

Pastikan semua test suite lolos sebelum mengajukan Pull Request:
```bash
# Jalankan seluruh unit test
php vendor/bin/phpunit

# Jalankan self-test engine konkurensi Pastroutine
php papastro pastroutine:test --count=1000

# Jalankan simulasi migrasi & auto-update
php papastro upgrade
```

