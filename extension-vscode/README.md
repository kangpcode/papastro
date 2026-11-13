# Papastro — VS Code & Antigravity IDE Extension

<p align="center">
  <img src="https://raw.githubusercontent.com/kangpcode/papastro/main/art/logo-landscape.png" width="380" alt="Papastro Logo">
</p>

<p align="center">
  <a href="https://github.com/kangpcode/papastro"><img src="https://img.shields.io/badge/version-4.0.0-blue.svg?style=flat-square" alt="Version"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-green.svg?style=flat-square" alt="License"></a>
  <img src="https://img.shields.io/badge/support-VS%20Code%20%7C%20Antigravity%20%7C%20Cursor-purple.svg?style=flat-square" alt="IDEs">
</p>

Official language support, TextMate syntax highlighting, code snippets, and file icon theme for the **Papastro Hybrid Framework** (`.pstro`).

Compatible with **Visual Studio Code**, **Google Antigravity IDE**, **Cursor**, **VSCodium**, and other VS Code-compatible editors.

---

## Fitur Utama

### 1. Ikon File Resmi (Papastro File Icon Theme)
- Ikon vektor SVG tajam dengan maskot resmi astronot Papastro untuk setiap file template `.pstro`.
- Ikon kustom untuk binary executable CLI `papastro`.
- Ikon gear konfigurasi untuk `papastro.config.js` / `papastro.config.ts`.
- Terintegrasi otomatis ke sidebar explorer editor Anda.

### 2. Penyorotan Sintaks Penuh (TextMate Grammar)
- **PHP Frontmatter**: Penyorotan kode PHP di dalam blok `--- ... ---` di awal file template.
- **Ekspresi Echo**:
  - `{{ $variable }}` (escaped HTML)
  - `{!! $raw !!}` & `{{{ $raw }}}` (raw HTML)
- **Directives Papastro**:
  - Layout: `@layout`, `@section`, `@endsection`, `@yield`, `@include`
  - Kontrol Alur: `@if`, `@elseif`, `@else`, `@endif`, `@unless`, `@endunless`
  - Perulangan: `@foreach`, `@endforeach`, `@forelse`, `@empty`, `@endforelse`, `@for`, `@while`
  - Keamanan & Auth: `@csrf`, `@method`, `@auth`, `@guest`, `@can`
- **Island Architecture Tags & Directives**:
  - Tag Papastro: `<island:load component="..." />`, `<island:visible ... />`, `<island:idle ... />`
  - Atribut Astro Island: `client:load`, `client:visible`, `client:idle`, `client:media`, `client:only`
  - Props binding: `:postId="$post->id"`
- **Embedded Languages**: Dukungan penuh untuk tag `<style>` (CSS) dan `<script>` (JavaScript/TypeScript).

### 3. Snippets Kode Cepat
Ketik prefix berikut untuk auto-complete template:
- `pstro-page`: Scaffold template halaman `.pstro` lengkap (frontmatter + layout + section).
- `pstro-frontmatter`: Blok PHP frontmatter (`---`).
- `pstro-island-load`: Tag island dengan hidrasi saat *page load*.
- `pstro-island-visible`: Tag island dengan hidrasi saat elemen masuk *viewport*.
- `pstro-island-idle`: Tag island dengan hidrasi saat browser *idle*.
- `pstro-component`: Komponen Astro-style dengan hidrasi selektif.
- `pstro-layout` & `pstro-section`: Directive inheritance master layout.
- `pstro-echo` & `pstro-raw`: Tag echo variabel PHP.
- `pstro-csrf` & `pstro-method`: Directive keamanan form.

### 4. Konfigurasi Bahasa (Language Features)
- Auto-closing tags & quotes.
- Bracket matching cerdas (`{ }`, `[ ]`, `( )`, `< >`).
- Comment toggling otomatis dengan shortcut keyboard (`Ctrl + /` atau `Cmd + /`).
- Code folding untuk frontmatter, sections, dan directives.

---

## Cara Instalasi

### Opsi 1: Instalasi File .VSIX (Satu Klik)
1. Di VS Code / Antigravity IDE, buka tab **Extensions** (`Ctrl + Shift + X`).
2. Klik menu titik tiga (**...**) di kanan atas panel Extensions.
3. Pilih **Install from VSIX...**
4. Arahkan ke file `papastro-vscode-4.0.0.vsix` di folder `extension-vscode/`.

### Opsi 2: Instalasi Manual Direktori
Salin folder `extension-vscode` ke direktori ekstensi IDE Anda:
- **VS Code**: `~/.vscode/extensions/papastro-vscode/`
- **Antigravity IDE**: `~/.antigravity/extensions/papastro-vscode/`
- **Cursor**: `~/.cursor/extensions/papastro-vscode/`

Restart IDE setelah penyalinan.

### Opsi 3: Mengaktifkan Tema Ikon Papastro
1. Buka Command Palette (`Ctrl + Shift + P` / `Cmd + Shift + P`).
2. Ketik **Preferences: File Icon Theme**.
3. Pilih **Papastro File Icons**.

---

## Cara Membangun Paket (.VSIX) dari Sumber

```bash
cd extension-vscode
npx --yes @vscode/vsce package --no-git-tag-version
```
Perintah ini akan menghasilkan file `papastro-vscode-4.0.0.vsix`.

---

## Panduan Publikasi ke Marketplace Resmi

### 1. Publikasi ke Visual Studio Marketplace (Microsoft)
*Menyediakan ekstensi untuk pengguna VS Code di seluruh dunia:*

1. Buat publisher `kangpcode` di [Marketplace Manage Portal](https://marketplace.visualstudio.com/manage).
2. Buat Personal Access Token (PAT) di Azure DevOps (`Marketplace: Manage`).
3. Jalankan publish via CLI:
   ```bash
   cd extension-vscode
   npx --yes @vscode/vsce login kangpcode
   npx --yes @vscode/vsce publish
   ```
   *Atau unggah langsung file `papastro-vscode-4.0.0.vsix` melalui tombol **New Extension → None (VSIX)** di portal.*

### 2. Publikasi ke Open VSX Registry (Antigravity IDE, Cursor & VSCodium)
*Menyediakan ekstensi untuk Google Antigravity IDE, Cursor, VSCodium, dan platform open-source:*

1. Masuk ke [open-vsx.org](https://open-vsx.org) menggunakan akun GitHub Anda.
2. Buat namespace `kangpcode` di menu profil Anda.
3. Buat Access Token di menu **Settings → Access Tokens**.
4. Terbitkan paket via CLI:
   ```bash
   cd extension-vscode
   npx --yes ovsx publish papastro-vscode-4.0.0.vsix -p <TOKEN_OPENVSX_ANDA>
   ```

---

## Lisensi & Hak Cipta

- **Author**: kangpcode (Dhafa Nazula Permadi)
- **License**: MIT License
- **Framework Website**: [https://github.com/kangpcode/papastro](https://github.com/kangpcode/papastro)

