<?php
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

declare(strict_types=1);

namespace Papastro\Island;

use Papastro\Foundation\Application;
use Papastro\Http\Request;

/**
 * IslandRenderer — renders island placeholders and generates hydration scripts.
 *
 * Each island is rendered server-side as a div placeholder with data attributes.
 * The HydrationEngine then picks up these placeholders and hydrates them client-side.
 */
class IslandRenderer
{
    protected Application $app;
    protected array $renderedIslands = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Render a static placeholder for an island.
     * Called by the compiled .pstro template during SSR.
     */
    public static function renderPlaceholder(
        string $id,
        string $component,
        string $strategy,
        array  $props = []
    ): void {
        $propsJson = htmlspecialchars(json_encode($props, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
        $strategy  = htmlspecialchars($strategy);
        $component = htmlspecialchars($component);

        echo <<<HTML
<div
    id="{$id}"
    data-papastro-island="{$component}"
    data-strategy="{$strategy}"
    data-props="{$propsJson}"
    class="papastro-island papastro-island--{$strategy}"
>
    <!-- Island: {$component} [{$strategy}] -->
    <div class="papastro-island-loading" aria-hidden="true"></div>
</div>
HTML;
    }

    /**
     * Render a full island-routed page (from Route::island).
     */
    public function render(string $component, array $parameters = []): string
    {
        $view = $this->app->make(\Papastro\View\ViewEngine::class);

        // Convert component path to module view: Blog/PostDetail → Blog::PostDetail
        $template = str_replace('/', '::', $component, $count);
        if ($count === 0) {
            $template = $component;
        }

        return $view->render($template, $parameters);
    }

    /**
     * Generate the hydration bootstrap script to inject into HTML.
     * This script handles client-side island hydration based on strategy.
     */
    public static function generateHydrationScript(): string
    {
        return <<<'JS'
<script>
/*
 * Papastro Framework — Island Hydration Engine
 * created by kangpcode (Dhafa Nazula Permadi)
 * Free to use & extend. This watermark must remain in source code.
 */
(function() {
    'use strict';

    const islands = document.querySelectorAll('[data-papastro-island]');

    const loadIsland = async (el) => {
        const component = el.dataset.papastroIsland;
        const props     = JSON.parse(el.dataset.props || '{}');
        const strategy  = el.dataset.strategy;

        try {
            // Dynamic import from /islands/{component}.js
            const module = await import(`/islands/${component}.js`);
            const Island = module.default;

            // Clear loading placeholder
            el.innerHTML = '';

            // Mount the island component
            if (typeof Island === 'function') {
                Island(el, props);
            } else if (Island && typeof Island.mount === 'function') {
                Island.mount(el, props);
            }

            el.classList.add('papastro-island--hydrated');
            el.classList.remove('papastro-island--' + strategy);

            // Dispatch hydration event
            el.dispatchEvent(new CustomEvent('papastro:hydrated', {
                detail: { component, props, strategy },
                bubbles: true
            }));
        } catch (err) {
            console.error(`[Papastro] Failed to hydrate island [${component}]:`, err);
            el.classList.add('papastro-island--error');
        }
    };

    islands.forEach(el => {
        const strategy = el.dataset.strategy;

        if (strategy === 'only') {
            // client:only — skip SSR placeholder, hydrate immediately
            loadIsland(el);
            return;
        }

        if (strategy === 'load') {
            // client:load — hydrate on page load
            loadIsland(el);
            return;
        }

        if (strategy === 'idle') {
            // client:idle — hydrate when browser is idle
            if ('requestIdleCallback' in window) {
                requestIdleCallback(() => loadIsland(el));
            } else {
                setTimeout(() => loadIsland(el), 200);
            }
            return;
        }

        if (strategy === 'visible') {
            // client:visible — hydrate when element enters viewport
            const observer = new IntersectionObserver((entries, obs) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        loadIsland(el);
                        obs.unobserve(el);
                    }
                });
            }, { rootMargin: '50px' });
            observer.observe(el);
            return;
        }
    });

    // Expose Papastro island API
    window.Papastro = window.Papastro || {};
    window.Papastro.islands = islands;
    window.Papastro.loadIsland = loadIsland;
    window.Papastro.version = '1.2.0';

})();
</script>
JS;
    }
}
