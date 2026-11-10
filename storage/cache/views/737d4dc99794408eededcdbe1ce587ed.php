<!--
  Papastro Framework — created by kangpcode (Dhafa Nazula Permadi)
  Free to use & extend. This watermark must remain in source code.
-->
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars((string)(config('app.locale', 'en')), ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo app(\Papastro\SEO\SeoManager::class)->render(); ?>
    <?php echo \Papastro\SEO\SeoManager::pwaHead(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/app.css">
    <?php echo $__sections['head'] ?? ''; ?>
</head>
<body>
    <nav class="navbar">
        <div class="container navbar-inner">
            <a href="/" class="navbar-brand">
                <span class="brand-icon">🌟</span>
                <span><?php echo htmlspecialchars((string)(config('app.name', 'Papastro')), ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
            <div class="navbar-links">
                <a href="/blog" class="nav-link">Blog</a>
                <a href="/login" class="nav-link">Login</a>
                <a href="/register" class="nav-btn btn btn-primary">Get Started</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <?php echo $__sections['content'] ?? ''; ?>
    </main>

    <footer class="footer">
        <div class="container">
            <p>Built with <span class="brand-icon">🌟</span> Papastro Framework &middot; &copy; <?php echo htmlspecialchars((string)(date('Y')), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </footer>

    <?php echo \Papastro\Island\IslandRenderer::generateHydrationScript(); ?>
    <?php echo \Papastro\SEO\SeoManager::swScript(); ?>

    <?php echo $__sections['scripts'] ?? ''; ?>
</body>
</html>
