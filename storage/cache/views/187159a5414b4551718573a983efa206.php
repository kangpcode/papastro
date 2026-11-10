<?php
$__sections['content'] = (function() use ($__data) {
    extract($__data, EXTR_SKIP);
    ob_start();
?>
<!--
  Papastro Framework — created by kangpcode (Dhafa Nazula Permadi)
  Free to use & extend. This watermark must remain in source code.
-->
---
// Welcome page — loaded from routes/web.php
$appName = config('app.name', 'Papastro');
$version = PAPASTRO_VERSION;
---


<?php $__sections['content'] = <<<'PSTRO_SECTION'

<div class="hero">
    <div class="container hero-inner">
        <div class="hero-badge">🚀 Now Available — v<?php echo htmlspecialchars((string)($version), ENT_QUOTES, 'UTF-8'); ?></div>
        <h1 class="hero-title">
            Build Faster with<br>
            <span class="gradient-text">Papastro Framework</span>
        </h1>
        <p class="hero-subtitle">
            Hybrid PHP Backend meets Astro-style Island Architecture.<br>
            Modular Monolith. Zero-JS by default. PWA-ready. SEO-first.
        </p>
        <div class="hero-actions">
            <a href="/blog" class="btn btn-primary btn-lg">Read the Blog →</a>
            <a href="https://github.com/kangpcode/papastro" class="btn btn-outline btn-lg">
                GitHub
            </a>
        </div>
        <div class="hero-code">
            <pre><code>composer create-project kangpcode/papastro my-app
cd my-app && php papastro dev</code></pre>
        </div>
    </div>
</div>

<section class="features">
    <div class="container">
        <h2 class="section-title">Everything You Need</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🏝️</div>
                <h3>Island Architecture</h3>
                <p>Pages render in PHP, interactive islands hydrate selectively — load, idle, or visible.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📦</div>
                <h3>Modular Monolith</h3>
                <p>Domain-driven modules, each with its own MVC + Routes + Islands + Pages.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔍</div>
                <h3>SEO-First</h3>
                <p>SSR by default, auto sitemap, Open Graph, Twitter Card, and JSON-LD helpers.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3>PWA Ready</h3>
                <p>Service worker, manifest.json, and offline caching generated with one command.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🛠️</div>
                <h3>Powerful CLI</h3>
                <p>30+ commands for modules, generators, migrations, debug, and optimization.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🐛</div>
                <h3>Debug Experience</h3>
                <p>Debugbar with N+1 detection + Next.js-style dev overlay for errors.</p>
            </div>
        </div>
    </div>
</section>

<section class="island-demo">
    <div class="container">
        <h2 class="section-title">Island Hydration Demo</h2>
        <p class="section-subtitle">This counter is an island — zero JS loaded until you scroll here.</p>
        <?php
$__islandProps = [];
<?php $__islandProps['initialCount'] = 0; ?>
<?php \Papastro\Island\IslandRenderer::renderPlaceholder('island-counter-c01a0e', 'Counter', 'visible', $__islandProps); ?>
    </div>
</section>

PSTRO_SECTION; ?>

<?php $__sections['scripts'] = <<<'PSTRO_SECTION'

<?php echo \Papastro\SEO\SeoManager::schema('WebSite', null); ?>

PSTRO_SECTION; ?>

<?php
    return ob_get_clean();
})();
echo app(\Papastro\View\ViewEngine::class)->render('layouts.base', array_merge($__data, ['__sections' => $__sections]));
?>