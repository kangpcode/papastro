<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v1.1.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Debugger;

use Papastro\Foundation\Application;
use Papastro\Http\Request;
use Papastro\Http\Response;

/**
 * Debugbar — injects a Laravel Debugbar-style debug panel into HTML responses.
 *
 * Shows:
 * - Query tab: SQL queries, timing, N+1 detection
 * - Routes tab: matched route, middleware
 * - Request tab: headers, session, input
 * - Islands tab: hydration status per island
 * - Timeline tab: server render time vs total
 */
class Debugbar
{
    protected Application $app;
    protected QueryCollector $queryCollector;
    protected array $islandLog = [];
    protected float $startTime;

    public function __construct(Application $app, QueryCollector $queryCollector)
    {
        $this->app            = $app;
        $this->queryCollector = $queryCollector;
        $this->startTime      = defined('PAPASTRO_START') ? PAPASTRO_START : microtime(true);
    }

    /**
     * Determine if the debugbar should be shown.
     */
    public function isEnabled(): bool
    {
        return $this->app->debug() && (bool) env('PAPASTRO_DEBUGBAR', true);
    }

    /**
     * Inject the debugbar HTML into the response.
     */
    public function inject(Response $response, Request $request): Response
    {
        if (!$this->isEnabled()) {
            return $response;
        }

        $content = $response->getContent();

        if (!str_contains($content, '</body>')) {
            return $response;
        }

        $debugbarHtml = $this->render($request);
        $content      = str_replace('</body>', $debugbarHtml . '</body>', $content);

        return $response->setContent($content);
    }

    /**
     * Render the debugbar HTML.
     */
    public function render(Request $request): string
    {
        $renderTime   = round((microtime(true) - $this->startTime) * 1000, 2);
        $queries      = $this->queryCollector->getQueries();
        $queryCount   = $this->queryCollector->count();
        $queryTime    = $this->queryCollector->totalTime();
        $n1Count      = count($this->queryCollector->getNPlusOneQueries());
        $route        = $request->route();
        $routeName    = $route?->getName() ?? 'N/A';
        $routeUri     = $route?->getUri() ?? $request->path();
        $middleware   = $route ? implode(', ', $route->getMiddleware()) : 'none';
        $memUsage     = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        $queryRows = '';
        foreach ($queries as $i => $q) {
            $n1Badge = $q['n1'] ? '<span class="pdb-badge pdb-badge--warn">N+1</span>' : '';
            $sql     = htmlspecialchars($q['sql']);
            $queryRows .= "<tr><td>{$i}</td><td class=\"pdb-sql\">{$sql}{$n1Badge}</td><td>{$q['elapsed']}ms</td></tr>";
        }

        $allHeaders = '';
        foreach ($request->headers() as $k => $v) {
            $allHeaders .= '<tr><td>' . htmlspecialchars($k) . '</td><td>' . htmlspecialchars($v) . '</td></tr>';
        }

        $allInput = '';
        foreach ($request->all() as $k => $v) {
            $allInput .= '<tr><td>' . htmlspecialchars($k) . '</td><td>' . htmlspecialchars((string) $v) . '</td></tr>';
        }

        $n1Warning = $n1Count > 0 ? "<span class=\"pdb-badge pdb-badge--danger\">{$n1Count} N+1</span>" : '';

        return <<<HTML
<link rel="stylesheet" href="/papastro-debugbar.css">
<div id="papastro-debugbar" class="pdb">
    <div class="pdb-bar">
        <span class="pdb-brand">🌟 Papastro</span>
        <span class="pdb-tab pdb-tab--active" data-tab="queries">
            Queries <span class="pdb-badge">{$queryCount}</span> {$n1Warning}
            <span class="pdb-time">{$queryTime}ms</span>
        </span>
        <span class="pdb-tab" data-tab="routes">Route</span>
        <span class="pdb-tab" data-tab="request">Request</span>
        <span class="pdb-tab" data-tab="timeline">Timeline</span>
        <span class="pdb-meta">
            {$renderTime}ms | {$memUsage}MB | PHP <?php echo PHP_VERSION; ?>
        </span>
        <button class="pdb-close" onclick="document.getElementById('papastro-debugbar').classList.toggle('pdb--minimized')">▼</button>
    </div>
    <div class="pdb-panels">
        <div class="pdb-panel pdb-panel--active" id="pdb-queries">
            <table class="pdb-table">
                <thead><tr><th>#</th><th>Query</th><th>Time</th></tr></thead>
                <tbody>{$queryRows}</tbody>
            </table>
        </div>
        <div class="pdb-panel" id="pdb-routes">
            <table class="pdb-table">
                <tr><td>Route</td><td><strong>{$routeName}</strong></td></tr>
                <tr><td>URI</td><td>{$routeUri}</td></tr>
                <tr><td>Method</td><td>{$request->method()}</td></tr>
                <tr><td>Middleware</td><td>{$middleware}</td></tr>
            </table>
        </div>
        <div class="pdb-panel" id="pdb-request">
            <h4>Headers</h4>
            <table class="pdb-table"><tbody>{$allHeaders}</tbody></table>
            <h4>Input</h4>
            <table class="pdb-table"><tbody>{$allInput}</tbody></table>
        </div>
        <div class="pdb-panel" id="pdb-timeline">
            <div class="pdb-timeline">
                <div class="pdb-timeline-bar" style="width: 100%">
                    <span>Total: {$renderTime}ms</span>
                </div>
                <div class="pdb-timeline-bar pdb-timeline-bar--db" style="width: <?php echo min(100, ($queryTime / max($renderTime, 1)) * 100); ?>%">
                    <span>DB: {$queryTime}ms</span>
                </div>
            </div>
            <p>Peak Memory: <strong>{$memUsage} MB</strong></p>
        </div>
    </div>
</div>
<script src="/papastro-debugbar.js"></script>
HTML;
    }

    /**
     * Log an island hydration event.
     */
    public function logIsland(string $component, string $strategy, float $elapsed): void
    {
        $this->islandLog[] = compact('component', 'strategy', 'elapsed');
    }
}
