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

namespace Papastro\Console\Commands;

use Papastro\Console\Command;

/**
 * papastro key:generate — generates a new APP_KEY
 */
class KeyGenerateCommand extends Command
{
    public function execute(array $args): int
    {
        $key = 'base64:' . base64_encode(random_bytes(32));
        $envPath = $this->app->basePath('.env');

        if (file_exists($envPath)) {
            $content = file_get_contents($envPath);

            if (str_contains($content, 'APP_KEY=')) {
                $content = preg_replace('/^APP_KEY=.*/m', "APP_KEY={$key}", $content);
            } else {
                $content .= "\nAPP_KEY={$key}\n";
            }

            file_put_contents($envPath, $content);
            $this->success("Application key set: {$key}");
        } else {
            $this->warn(".env file not found. Copy .env.example first: cp .env.example .env");
            $this->info("Generated key: {$key}");
        }

        return 0;
    }
}

/**
 * papastro cache:clear — clears all caches
 */
class CacheClearCommand extends Command
{
    public function execute(array $args): int
    {
        $dirs = [
            $this->app->storagePath('cache/views'),
            $this->app->storagePath('cache/routes'),
            $this->app->storagePath('cache/config'),
        ];

        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                foreach (glob("{$dir}/*.php") as $file) {
                    unlink($file);
                }
            }
        }

        $this->success('Cache cleared successfully.');
        return 0;
    }
}

/**
 * papastro optimize — optimizes the application
 */
class OptimizeCommand extends Command
{
    public function execute(array $args): int
    {
        $quiet = $this->hasFlag($args, 'quiet');

        if (!$quiet)
            $this->info('Optimizing application...');

        // Clear old caches
        (new CacheClearCommand($this->app, $this->console))->execute([]);

        // Create storage directories
        $dirs = [
            $this->app->storagePath('logs'),
            $this->app->storagePath('cache/views'),
            $this->app->storagePath('cache/routes'),
            $this->app->storagePath('cache/config'),
            $this->app->storagePath('debug'),
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir))
                mkdir($dir, 0755, true);
        }

        if (!$quiet)
            $this->success('Application optimized.');
        return 0;
    }
}

/**
 * papastro route:cache — caches route list
 */
class RouteCacheCommand extends Command
{
    public function execute(array $args): int
    {
        $this->info('Caching routes...');
        $router = $this->app->make(\Papastro\Router\Router::class);
        $routes = $router->getRoutes()->all();
        $cacheData = [];

        foreach ($routes as $route) {
            $cacheData[] = [
                'methods' => $route->getMethods(),
                'uri' => $route->getUri(),
                'name' => $route->getName(),
            ];
        }

        $cachePath = $this->app->storagePath('cache/routes/routes.php');
        $this->mkdir(dirname($cachePath));
        file_put_contents($cachePath, '<?php return ' . var_export($cacheData, true) . ';');

        $this->success('Routes cached (' . count($cacheData) . ' routes).');
        return 0;
    }
}

/**
 * papastro route:list — lists all routes
 */
class RouteListCommand extends Command
{
    public function execute(array $args): int
    {
        $router = $this->app->make(\Papastro\Router\Router::class);
        $routes = $router->getRoutes()->all();

        $this->newLine();
        $this->line('  ' . str_pad('Method', 10) . str_pad('URI', 40) . str_pad('Name', 30) . 'Middleware', 'yellow');
        $this->line('  ' . str_repeat('─', 90), 'gray');

        foreach ($routes as $route) {
            $method = implode('|', $route->getMethods());
            $uri = $route->getUri();
            $name = $route->getName() ?? '-';
            $mw = implode(',', $route->getMiddleware()) ?: '-';

            echo '  ' . "\033[32m" . str_pad($method, 10) . "\033[0m"
                . str_pad($uri, 40)
                . "\033[33m" . str_pad($name, 30) . "\033[0m"
                . "\033[90m" . $mw . "\033[0m"
                . PHP_EOL;
        }

        $this->newLine();
        $this->line('  Total: ' . count($routes) . ' routes', 'gray');
        return 0;
    }
}

/**
 * papastro seo:sitemap — generates sitemap.xml
 */
class SeoSitemapCommand extends Command
{
    public function execute(array $args): int
    {
        $this->info('Generating sitemap.xml...');
        $router = $this->app->make(\Papastro\Router\Router::class);
        $generator = new \Papastro\SEO\SitemapGenerator($this->app, $router->getRoutes());
        $path = $generator->generate();
        $this->success("Sitemap generated: {$path}");
        return 0;
    }
}

/**
 * papastro pwa:generate — generates manifest.json and sw.js
 */
class PwaGenerateCommand extends Command
{
    public function execute(array $args): int
    {
        $this->info('Generating PWA files...');
        $pwa = $this->app->make(\Papastro\PWA\PwaManager::class);

        $manifestPath = $pwa->generateManifest();
        $this->success("manifest.json generated: {$manifestPath}");

        $swPath = $pwa->generateServiceWorker();
        $this->success("sw.js generated: {$swPath}");

        return 0;
    }
}

/**
 * papastro pwa:precache — updates precache list in sw.js
 */
class PwaPrecacheCommand extends Command
{
    public function execute(array $args): int
    {
        // Regenerate service worker (which includes precache list from config)
        return (new PwaGenerateCommand($this->app, $this->console))->execute($args);
    }
}

/**
 * papastro debug:route — list route details
 */
class DebugRouteCommand extends Command
{
    public function execute(array $args): int
    {
        return (new RouteListCommand($this->app, $this->console))->execute($args);
    }
}

/**
 * papastro debug:island — check island status
 */
class DebugIslandCommand extends Command
{
    public function execute(array $args): int
    {
        $this->info('Island Debug — scanning for island files...');
        $this->newLine();

        $modulesPath = $this->app->appPath('Modules');
        $count = 0;

        foreach (glob("{$modulesPath}/*/Islands/*.js") as $file) {
            $rel = str_replace($this->app->basePath() . DIRECTORY_SEPARATOR, '', $file);
            $this->line("  → {$rel}", 'cyan');
            $count++;
        }

        foreach (glob($this->app->resourcePath('islands/*.js')) as $file) {
            $rel = str_replace($this->app->basePath() . DIRECTORY_SEPARATOR, '', $file);
            $this->line("  → {$rel}", 'cyan');
            $count++;
        }

        $this->newLine();
        $this->line("  Total: {$count} islands found", 'gray');
        return 0;
    }
}

/**
 * papastro debug:clear — clear debug snapshots
 */
class DebugClearCommand extends Command
{
    public function execute(array $args): int
    {
        $debugDir = $this->app->storagePath('debug');
        if (is_dir($debugDir)) {
            foreach (glob("{$debugDir}/*") as $file) {
                if (is_file($file))
                    unlink($file);
            }
        }
        $this->success('Debug snapshots cleared.');
        return 0;
    }
}

/**
 * papastro migrate — run database migrations
 */
class MigrateCommand extends Command
{
    public function execute(array $args): int
    {
        $this->info('Running migrations...');
        $migrator = new \Papastro\ORM\Migrator(
            $this->app->make(\Papastro\ORM\Connection::class),
            $this->app->databasePath('migrations')
        );
        $ran = $migrator->run();

        if (empty($ran)) {
            $this->line('  Nothing to migrate.', 'gray');
        } else {
            foreach ($ran as $migration) {
                $this->success("Migrated: {$migration}");
            }
        }
        return 0;
    }
}

/**
 * papastro migrate:rollback
 */
class MigrateRollbackCommand extends Command
{
    public function execute(array $args): int
    {
        $this->info('Rolling back last migration batch...');
        $migrator = new \Papastro\ORM\Migrator(
            $this->app->make(\Papastro\ORM\Connection::class),
            $this->app->databasePath('migrations')
        );
        $rolled = $migrator->rollback();

        foreach ($rolled as $migration) {
            $this->success("Rolled back: {$migration}");
        }
        return 0;
    }
}

/**
 * papastro db:seed
 */
class DbSeedCommand extends Command
{
    public function execute(array $args): int
    {
        $seederClass = $this->option($args, 'class', 'DatabaseSeeder');
        $seederPath = $this->app->databasePath("seeders/{$seederClass}.php");

        if (!file_exists($seederPath)) {
            $this->error("Seeder [{$seederClass}] not found.");
            return 1;
        }

        require_once $seederPath;

        $seeder = new $seederClass($this->app->make(\Papastro\ORM\Connection::class));
        $seeder->run();

        $this->success("Seeder [{$seederClass}] ran successfully.");
        return 0;
    }
}

/**
 * papastro dev — start development server
 */
class DevServerCommand extends Command
{
    public function execute(array $args): int
    {
        $host = $this->option($args, 'host', '127.0.0.1');
        $port = $this->option($args, 'port', '8000');

        $this->newLine();
        $this->line("  🌟  Papastro Framework " . \Papastro\Foundation\Application::VERSION, 'purple');
        $this->newLine();
        $this->success("Development server started!");
        $this->info("  Local:   http://{$host}:{$port}");
        $this->info("  Press Ctrl+C to stop.");
        $this->newLine();

        $docRoot = $this->app->publicPath();
        $cmd = "php -S {$host}:{$port} -t \"{$docRoot}\" \"{$docRoot}/index.php\"";
        passthru($cmd);
        return 0;
    }
}

/**
 * papastro serve — alias for dev server in production preview mode
 */
class ServeCommand extends Command
{
    public function execute(array $args): int
    {
        return (new DevServerCommand($this->app, $this->console))->execute($args);
    }
}

/**
 * papastro build — build production assets
 */
class BuildCommand extends Command
{
    public function execute(array $args): int
    {
        $this->info('Building for production...');

        // Run optimize
        (new OptimizeCommand($this->app, $this->console))->execute([]);

        // Generate PWA files
        (new PwaGenerateCommand($this->app, $this->console))->execute([]);

        // Generate sitemap
        (new SeoSitemapCommand($this->app, $this->console))->execute([]);

        // Cache routes
        (new RouteCacheCommand($this->app, $this->console))->execute([]);

        $this->newLine();
        $this->success('Build complete! Your app is ready for production.');
        return 0;
    }
}
