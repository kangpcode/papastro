<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Console;

use Papastro\Foundation\Application;

/**
 * Console Kernel — runs CLI commands.
 */
class Kernel
{
    protected Application $app;
    protected array $commands = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->registerDefaultCommands();
    }

    /**
     * Register the built-in Papastro commands.
     */
    protected function registerDefaultCommands(): void
    {
        $this->commands = [
            // Core
            'key:generate'        => Commands\KeyGenerateCommand::class,
            'optimize'            => Commands\OptimizeCommand::class,
            'cache:clear'         => Commands\CacheClearCommand::class,
            'route:cache'         => Commands\RouteCacheCommand::class,
            'route:list'          => Commands\RouteListCommand::class,

            // Module
            'module:make'         => Commands\MakeModuleCommand::class,
            'module:enable'       => Commands\ModuleEnableCommand::class,
            'module:disable'      => Commands\ModuleDisableCommand::class,
            'module:list'         => Commands\ModuleListCommand::class,

            // Generators
            'make:controller'     => Commands\MakeControllerCommand::class,
            'make:model'          => Commands\MakeModelCommand::class,
            'make:migration'      => Commands\MakeMigrationCommand::class,
            'make:island'         => Commands\MakeIslandCommand::class,
            'make:page'           => Commands\MakePageCommand::class,
            'make:middleware'     => Commands\MakeMiddlewareCommand::class,
            'make:service'        => Commands\MakeServiceCommand::class,

            // Database
            'migrate'             => Commands\MigrateCommand::class,
            'migrate:rollback'    => Commands\MigrateRollbackCommand::class,
            'db:seed'             => Commands\DbSeedCommand::class,

            // SEO & PWA
            'seo:sitemap'         => Commands\SeoSitemapCommand::class,
            'pwa:generate'        => Commands\PwaGenerateCommand::class,
            'pwa:precache'        => Commands\PwaPrecacheCommand::class,

            // Debug
            'debug:route'         => Commands\DebugRouteCommand::class,
            'debug:island'        => Commands\DebugIslandCommand::class,
            'debug:clear'         => Commands\DebugClearCommand::class,

            // Dev server
            'dev'                 => Commands\DevServerCommand::class,
            'serve'               => Commands\ServeCommand::class,
            'build'               => Commands\BuildCommand::class,
        ];
    }

    /**
     * Handle an incoming CLI command.
     */
    public function handle(array $argv): int
    {
        $commandName = $argv[1] ?? null;
        $args        = array_slice($argv, 2);

        if (!$commandName || in_array($commandName, ['--help', '-h', 'help'])) {
            $this->showHelp();
            return 0;
        }

        if ($commandName === '--version' || $commandName === '-V') {
            $this->output("Papastro Framework " . Application::VERSION, 'green');
            return 0;
        }

        if (!isset($this->commands[$commandName])) {
            $this->output("Command [{$commandName}] not found.", 'red');
            $this->output("Run 'php papastro --help' to see available commands.", 'yellow');
            return 1;
        }

        $commandClass = $this->commands[$commandName];
        $command      = new $commandClass($this->app, $this);

        try {
            return $command->execute($args);
        } catch (\Throwable $e) {
            $this->output("Error: " . $e->getMessage(), 'red');
            if ($this->app->debug()) {
                $this->output($e->getTraceAsString(), 'gray');
            }
            return 1;
        }
    }

    /**
     * Show the help screen.
     */
    protected function showHelp(): void
    {
        $this->output("", 'default');
        $this->output("  🌟  Papastro Framework " . Application::VERSION, 'purple');
        $this->output("  Hybrid PHP + Astro-style Modular Monolith Framework", 'gray');
        $this->output("  @author kangpcode (Dhafa Nazula Permadi)", 'gray');
        $this->output("", 'default');
        $this->output("  Usage: php papastro <command> [options]", 'white');
        $this->output("", 'default');

        $groups = [
            'Server' => ['dev', 'serve', 'build'],
            'Module' => ['module:make', 'module:enable', 'module:disable', 'module:list'],
            'Generator' => ['make:controller', 'make:model', 'make:migration', 'make:island', 'make:page', 'make:middleware', 'make:service'],
            'Database' => ['migrate', 'migrate:rollback', 'db:seed'],
            'SEO & PWA' => ['seo:sitemap', 'pwa:generate', 'pwa:precache'],
            'Debug' => ['debug:route', 'debug:island', 'debug:clear'],
            'Cache & Optimize' => ['cache:clear', 'optimize', 'route:cache', 'route:list'],
            'App' => ['key:generate'],
        ];

        foreach ($groups as $group => $cmds) {
            $this->output("  {$group}", 'yellow');
            foreach ($cmds as $cmd) {
                $this->output("    php papastro {$cmd}", 'white');
            }
            $this->output("", 'default');
        }
    }

    /**
     * Output a line to the terminal with color.
     */
    public function output(string $message, string $color = 'default'): void
    {
        $colors = [
            'default' => "\033[0m",
            'black'   => "\033[30m",
            'red'     => "\033[31m",
            'green'   => "\033[32m",
            'yellow'  => "\033[33m",
            'blue'    => "\033[34m",
            'purple'  => "\033[35m",
            'cyan'    => "\033[36m",
            'white'   => "\033[37m",
            'gray'    => "\033[90m",
        ];

        $code  = $colors[$color] ?? "\033[0m";
        $reset = "\033[0m";

        echo $code . $message . $reset . PHP_EOL;
    }

    /**
     * Output a success message.
     */
    public function success(string $message): void
    {
        $this->output("  ✓  {$message}", 'green');
    }

    /**
     * Output an error message.
     */
    public function error(string $message): void
    {
        $this->output("  ✗  {$message}", 'red');
    }

    /**
     * Output an info message.
     */
    public function info(string $message): void
    {
        $this->output("  →  {$message}", 'cyan');
    }

    /**
     * Output a warning message.
     */
    public function warn(string $message): void
    {
        $this->output("  !  {$message}", 'yellow');
    }
}
