# Changelog — Papastro Extension for VS Code & Antigravity IDE

## [3.0.0] - 2026-11-11

### Initial Release
- **File Icons**: Official Papastro logo icon theme for `.pstro` files, `papastro` CLI executable, and config files.
- **Syntax Highlighting**: Comprehensive TextMate grammar (`text.html.papastro`) supporting:
  - PHP Frontmatter (`--- ... ---`)
  - Escaped (`{{ }}`) and Raw (`{!! !!}`) echo tags
  - Papastro/Blade directives (`@layout`, `@section`, `@yield`, `@if`, `@foreach`, `@csrf`, etc.)
  - Island Architecture tags (`<island:load>`, `<island:visible>`, `<island:idle>`)
  - Astro-style client hydration attributes (`client:load`, `client:visible`, `client:idle`, `client:media`, `client:only`)
  - Embedded CSS and JavaScript
- **Code Snippets**: Quick templates for `.pstro` pages, layouts, islands, and directives.
- **Language Configuration**: Auto-closing brackets, tag pairing, and code folding.
- **Cross-IDE Compatibility**: Verified for VS Code, Google Antigravity IDE, Cursor, and VSCodium.

