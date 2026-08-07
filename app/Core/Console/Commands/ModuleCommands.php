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

namespace Papastro\Console\Commands;

use Papastro\Console\Command;

/**
 * Generate a new module scaffold: papastro module:make BlogModule
 */
class MakeModuleCommand extends Command
{
    public function execute(array $args): int
    {
        $name = $this->argument($args, 0);
        if (!$name) {
            $this->error('Module name is required. Usage: papastro module:make <Name>');
            return 1;
        }

        $name      = ucfirst($name);
        $basePath  = $this->app->appPath("Modules/{$name}");
        $ns        = "App\\Modules\\{$name}";

        $this->info("Creating module: {$name}");
        $this->newLine();

        // Directory structure
        $dirs = [
            'Http/Controllers',
            'Http/Middleware',
            'Models',
            'Services',
            'Repositories',
            'Routes',
            'Islands',
            'Pages',
            'Events',
            'Listeners',
            'Tests',
        ];

        foreach ($dirs as $dir) {
            $this->mkdir("{$basePath}/{$dir}");
        }

        // module.json
        $this->writeFile("{$basePath}/module.json", json_encode([
            'name'        => $name,
            'description' => "{$name} module",
            'version'     => '1.0.0',
            'enabled'     => true,
            'requires'    => [],
            'author'      => 'kangpcode',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Routes/web.php
        $this->writeFile("{$basePath}/Routes/web.php", $this->getHeader() . <<<PHP

use Papastro\Router\RouteFacade as Route;
use {$ns}\\Http\\Controllers\\{$name}Controller;

Route::get('/{$this->toSlug($name)}', [{$name}Controller::class, 'index'])->name('{$this->toSlug($name)}.index');
PHP);

        // Routes/api.php
        $this->writeFile("{$basePath}/Routes/api.php", $this->getHeader() . <<<PHP

use Papastro\Router\RouteFacade as Route;
use {$ns}\\Http\\Controllers\\{$name}Controller;

Route::get('/{$this->toSlug($name)}', [{$name}Controller::class, 'index'])->name('api.{$this->toSlug($name)}.index');
PHP);

        // Controller
        $this->writeFile("{$basePath}/Http/Controllers/{$name}Controller.php", $this->getHeader() . <<<PHP

declare(strict_types=1);

namespace {$ns}\\Http\\Controllers;

use Papastro\\Http\\Request;
use Papastro\\Http\\Response;

class {$name}Controller
{
    public function index(): Response
    {
        return view('{$name}::index');
    }
}
PHP);

        // Model
        $modelName = rtrim($name, 's');
        $this->writeFile("{$basePath}/Models/{$modelName}.php", $this->getHeader() . <<<PHP

declare(strict_types=1);

namespace {$ns}\\Models;

use Papastro\\ORM\\Model;

class {$modelName} extends Model
{
    protected string \$table    = '{$this->toSlug($name)}';
    protected array  \$fillable = [];
    protected bool   \$timestamps = true;
}
PHP);

        // Pages/index.pstro
        $this->writeFile("{$basePath}/Pages/index.pstro", $this->getPstroHeader() . <<<PSTRO

---
// {$name} Module — index page
\$pageTitle = '{$name}';
---
@layout('layouts.base')

@section('content')
<div class="container">
    <h1>{{ \$pageTitle }}</h1>
    <p>Welcome to the {$name} module.</p>
</div>
@endsection
PSTRO);

        // Service Provider
        $this->writeFile("{$basePath}/{$name}ServiceProvider.php", $this->getHeader() . <<<PHP

declare(strict_types=1);

namespace {$ns};

use Papastro\\Foundation\\ServiceProvider;

class {$name}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register {$name} module bindings here.
    }

    public function boot(): void
    {
        // Boot {$name} module services here.
    }
}
PHP);

        $this->newLine();
        $this->success("Module [{$name}] created successfully!");
        $this->info("Path: app/Modules/{$name}/");
        return 0;
    }

    protected function toSlug(string $name): string
    {
        return strtolower(preg_replace('/([A-Z])/', '-$1', lcfirst($name)));
    }
}

/**
 * Enable a module: papastro module:enable Blog
 */
class ModuleEnableCommand extends Command
{
    public function execute(array $args): int
    {
        $name = $this->argument($args, 0);
        if (!$name) { $this->error('Module name required.'); return 1; }

        $manager = $this->app->make(\Papastro\Module\ModuleManager::class);
        if ($manager->enable($name)) {
            $this->success("Module [{$name}] enabled.");
        } else {
            $this->error("Module [{$name}] not found.");
            return 1;
        }
        return 0;
    }
}

/**
 * Disable a module: papastro module:disable Blog
 */
class ModuleDisableCommand extends Command
{
    public function execute(array $args): int
    {
        $name = $this->argument($args, 0);
        if (!$name) { $this->error('Module name required.'); return 1; }

        $manager = $this->app->make(\Papastro\Module\ModuleManager::class);
        if ($manager->disable($name)) {
            $this->success("Module [{$name}] disabled.");
        } else {
            $this->error("Module [{$name}] not found.");
            return 1;
        }
        return 0;
    }
}

/**
 * List all modules: papastro module:list
 */
class ModuleListCommand extends Command
{
    public function execute(array $args): int
    {
        $manager = $this->app->make(\Papastro\Module\ModuleManager::class);
        $modules = $manager->all();

        $this->newLine();
        $this->line('  Modules', 'yellow');
        $this->line('  ' . str_repeat('─', 50), 'gray');

        if (empty($modules)) {
            $this->warn('No modules found in app/Modules/');
            return 0;
        }

        foreach ($modules as $mod) {
            $status = $mod['enabled'] ? "\033[32m● enabled\033[0m" : "\033[31m○ disabled\033[0m";
            $loaded = $mod['loaded']  ? ' [loaded]' : '';
            echo "  {$mod['name']} v{$mod['version']} — {$status}{$loaded}" . PHP_EOL;
        }

        $this->newLine();
        return 0;
    }
}
