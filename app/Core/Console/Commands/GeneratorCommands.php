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
 * papastro make:controller Blog/PostController
 */
class MakeControllerCommand extends Command
{
    public function execute(array $args): int
    {
        $name = $this->argument($args, 0);
        if (!$name) {
            $this->error('Usage: papastro make:controller Module/ControllerName');
            return 1;
        }

        [$module, $class] = $this->parseName($name);
        $path = $this->app->appPath("Modules/{$module}/Http/Controllers/{$class}.php");
        $ns = "App\\Modules\\{$module}\\Http\\Controllers";

        $isResource = $this->hasFlag($args, 'resource');

        $methods = $isResource ? $this->resourceMethods($class) : <<<PHP
    public function index(): \\Papastro\\Http\\Response
    {
        return view('{$module}::index');
    }
PHP;

        return $this->writeFile(
            $path,
            $this->getHeader() . <<<PHP

declare(strict_types=1);

namespace {$ns};

use Papastro\\Http\\Request;
use Papastro\\Http\\Response;

class {$class}
{
{$methods}
}
PHP
        ) ? 0 : 1;
    }

    protected function resourceMethods(string $class): string
    {
        return <<<PHP
    public function index(): Response { return view(''); }
    public function create(): Response { return view(''); }
    public function store(Request \$request): Response { return redirect('/'); }
    public function show(string \$id): Response { return view(''); }
    public function edit(string \$id): Response { return view(''); }
    public function update(Request \$request, string \$id): Response { return redirect('/'); }
    public function destroy(string \$id): Response { return redirect('/'); }
PHP;
    }

    protected function parseName(string $name): array
    {
        if (str_contains($name, '/')) {
            $parts = explode('/', $name);
            $class = array_pop($parts);
            return [implode('/', $parts), $class];
        }
        return ['App', $name];
    }
}

/**
 * papastro make:model Blog/Post
 */
class MakeModelCommand extends Command
{
    public function execute(array $args): int
    {
        $name = $this->argument($args, 0);
        if (!$name) {
            $this->error('Usage: papastro make:model Module/ModelName');
            return 1;
        }

        [$module, $class] = $this->parseName($name);
        $path = $this->app->appPath("Modules/{$module}/Models/{$class}.php");
        $ns = "App\\Modules\\{$module}\\Models";
        $table = strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst($class))) . 's';

        return $this->writeFile(
            $path,
            $this->getHeader() . <<<PHP

declare(strict_types=1);

namespace {$ns};

use Papastro\\ORM\\Model;

class {$class} extends Model
{
    protected string \$table    = '{$table}';
    protected array  \$fillable = [];
    protected bool   \$timestamps = true;
}
PHP
        ) ? 0 : 1;
    }

    protected function parseName(string $name): array
    {
        if (str_contains($name, '/')) {
            $parts = explode('/', $name);
            $class = array_pop($parts);
            return [implode('/', $parts), $class];
        }
        return ['App', $name];
    }
}

/**
 * papastro make:migration create_posts_table
 */
class MakeMigrationCommand extends Command
{
    public function execute(array $args): int
    {
        $name = $this->argument($args, 0);
        if (!$name) {
            $this->error('Usage: papastro make:migration migration_name');
            return 1;
        }

        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.php";
        $path = $this->app->databasePath("migrations/{$filename}");
        $class = str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));

        return $this->writeFile(
            $path,
            $this->getHeader() . <<<PHP

declare(strict_types=1);

use Papastro\\ORM\\Migration;

return new class extends Migration {
    public function up(): void
    {
        \$this->create('{$this->guessTable($name)}', function (\$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        \$this->dropIfExists('{$this->guessTable($name)}');
    }
};
PHP
        ) ? 0 : 1;
    }

    protected function guessTable(string $name): string
    {
        // create_posts_table → posts
        if (preg_match('/create_(.+)_table/', $name, $m))
            return $m[1];
        return $name;
    }
}

/**
 * papastro make:island Blog/CommentBox
 */
class MakeIslandCommand extends Command
{
    public function execute(array $args): int
    {
        $name = $this->argument($args, 0);
        if (!$name) {
            $this->error('Usage: papastro make:island Module/IslandName');
            return 1;
        }

        [$module, $class] = $this->parseName($name);
        $path = $this->app->appPath("Modules/{$module}/Islands/{$class}.js");

        $content = $this->getIslandHeader() . <<<JS


/**
 * {$class} Island Component
 * Usage in .pstro: <{$class} client:visible />
 */
export default function {$class}(el, props) {
    // props contains the server-side data passed to the island
    console.log('[{$class}] Hydrating with props:', props);

    // Render your component here
    el.innerHTML = \`
        <div class="{$this->toKebab($class)}">
            <p>Hello from {$class} island!</p>
        </div>
    \`;

    // Add interactivity
    // el.querySelector('button')?.addEventListener('click', () => { ... });
}
JS;

        return $this->writeFile($path, $content) ? 0 : 1;
    }

    protected function parseName(string $name): array
    {
        if (str_contains($name, '/')) {
            $parts = explode('/', $name);
            $class = array_pop($parts);
            return [implode('/', $parts), $class];
        }
        return ['App', $name];
    }

    protected function toKebab(string $name): string
    {
        return strtolower(preg_replace('/([A-Z])/', '-$1', lcfirst($name)));
    }
}

/**
 * papastro make:page Blog/index
 */
class MakePageCommand extends Command
{
    public function execute(array $args): int
    {
        $name = $this->argument($args, 0);
        if (!$name) {
            $this->error('Usage: papastro make:page Module/pagename');
            return 1;
        }

        [$module, $page] = $this->parseName($name);
        $path = $this->app->appPath("Modules/{$module}/Pages/{$page}.pstro");

        $content = $this->getPstroHeader() . <<<PSTRO


---
// {$module} — {$page} page (server-side PHP runs here)
\$pageTitle = '{$page}';
---
@layout('layouts.base')

@section('content')
<div class="container">
    <h1>{{ \$pageTitle }}</h1>
</div>
@endsection
PSTRO;

        return $this->writeFile($path, $content) ? 0 : 1;
    }

    protected function parseName(string $name): array
    {
        if (str_contains($name, '/')) {
            $parts = explode('/', $name);
            $page = array_pop($parts);
            return [implode('/', $parts), $page];
        }
        return ['App', $name];
    }
}

/**
 * papastro make:middleware AuthCheck
 */
class MakeMiddlewareCommand extends Command
{
    public function execute(array $args): int
    {
        $name = $this->argument($args, 0);
        if (!$name) {
            $this->error('Usage: papastro make:middleware MiddlewareName');
            return 1;
        }

        $path = $this->app->appPath("Http/Middleware/{$name}.php");

        return $this->writeFile(
            $path,
            $this->getHeader() . <<<PHP

declare(strict_types=1);

namespace App\\Http\\Middleware;

use Papastro\\Http\\Request;
use Papastro\\Http\\Response;

class {$name}
{
    public function handle(Request \$request, callable \$next): Response
    {
        // Add your middleware logic before the request...

        \$response = \$next(\$request);

        // Add your middleware logic after the response...

        return \$response;
    }
}
PHP
        ) ? 0 : 1;
    }
}

/**
 * papastro make:service Blog/PostService
 */
class MakeServiceCommand extends Command
{
    public function execute(array $args): int
    {
        $name = $this->argument($args, 0);
        if (!$name) {
            $this->error('Usage: papastro make:service Module/ServiceName');
            return 1;
        }

        [$module, $class] = $this->parseName($name);
        $path = $this->app->appPath("Modules/{$module}/Services/{$class}.php");
        $ns = "App\\Modules\\{$module}\\Services";

        return $this->writeFile(
            $path,
            $this->getHeader() . <<<PHP

declare(strict_types=1);

namespace {$ns};

class {$class}
{
    public function __construct()
    {
        // Inject your dependencies here
    }

    // Add your service methods here
}
PHP
        ) ? 0 : 1;
    }

    protected function parseName(string $name): array
    {
        if (str_contains($name, '/')) {
            $parts = explode('/', $name);
            $class = array_pop($parts);
            return [implode('/', $parts), $class];
        }
        return ['App', $name];
    }
}

/**
 * papastro make:event Blog/PostPublished
 */
class MakeEventCommand extends Command
{
    public function execute(array $args): int
    {
        $name = $this->argument($args, 0);
        if (!$name) {
            $this->error('Usage: papastro make:event Module/EventName');
            return 1;
        }

        if (str_contains($name, '/')) {
            [$module, $class] = explode('/', $name, 2);
            $path = $this->app->appPath("Modules/{$module}/Events/{$class}.php");
            $ns = "App\\Modules\\{$module}\\Events";
        } else {
            $class = $name;
            $path = $this->app->appPath("Events/{$class}.php");
            $ns = "App\\Events";
        }

        return $this->writeFile(
            $path,
            $this->getHeader() . <<<PHP

declare(strict_types=1);

namespace {$ns};

class {$class}
{
    public mixed \$data;

    public function __construct(mixed \$data = null)
    {
        \$this->data = \$data;
    }
}
PHP
        ) ? 0 : 1;
    }
}

/**
 * papastro make:listener Blog/SendPostNotification
 */
class MakeListenerCommand extends Command
{
    public function execute(array $args): int
    {
        $name = $this->argument($args, 0);
        if (!$name) {
            $this->error('Usage: papastro make:listener Module/ListenerName');
            return 1;
        }

        if (str_contains($name, '/')) {
            [$module, $class] = explode('/', $name, 2);
            $path = $this->app->appPath("Modules/{$module}/Listeners/{$class}.php");
            $ns = "App\\Modules\\{$module}\\Listeners";
        } else {
            $class = $name;
            $path = $this->app->appPath("Listeners/{$class}.php");
            $ns = "App\\Listeners";
        }

        return $this->writeFile(
            $path,
            $this->getHeader() . <<<PHP

declare(strict_types=1);

namespace {$ns};

class {$class}
{
    public function handle(object \$event): void
    {
        // Handle the event here
    }
}
PHP
        ) ? 0 : 1;
    }
}
