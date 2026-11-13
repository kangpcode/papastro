<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v3.0.0
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
 * First-time project setup: papastro install [--mode=modules|full]
 *
 * The selected mode is stored in .env so one project stays lean (modules only)
 * while another can opt into the plug-and-play plugin ecosystem.
 */
class InstallCommand extends Command
{
    public const MODE_MODULES = 'modules';
    public const MODE_FULL = 'full';

    public function execute(array $args): int
    {
        $this->newLine();
        $this->line('  Papastro Installer', 'purple');
        $this->line('  Hybrid PHP + Astro-style Modular Monolith Framework', 'gray');
        $this->newLine();

        $mode = $this->resolveMode($args);
        if ($mode === null) {
            return 1;
        }

        if (!$this->prepareEnvironment($mode)) {
            return 1;
        }

        $this->prepareDirectories($mode);
        $this->clearCachedConfiguration();

        $this->newLine();
        $this->success("Installation mode: {$mode}");

        if ($mode === self::MODE_MODULES) {
            $this->info('Only app/Modules is active — the plugin system is disabled.');
        } else {
            $this->info('app/Modules and plugins/ are active.');
        }

        $this->newLine();
        $this->line('  Next steps:', 'yellow');
        $this->line('    php papastro migrate', 'white');
        $this->line('    php papastro dev', 'white');
        $this->newLine();

        return 0;
    }

    /**
     * Resolve the installation mode from a flag, or ask the user once.
     */
    protected function resolveMode(array $args): ?string
    {
        $option = $this->option($args, 'mode');
        if (is_string($option) && trim($option) !== '') {
            return $this->normalizeMode($option);
        }

        if ($this->hasFlag($args, 'modules-only') || $this->hasFlag($args, 'minimal')) {
            return self::MODE_MODULES;
        }

        if ($this->hasFlag($args, 'with-plugins') || $this->hasFlag($args, 'plugins')) {
            return self::MODE_FULL;
        }

        if (!$this->isInteractive() || $this->hasFlag($args, 'no-interaction') || $this->hasFlag($args, 'quiet')) {
            return self::MODE_FULL;
        }

        $this->line('  Choose how this project will be built:', 'yellow');
        $this->line('    [1] Modules only      app/Modules saja, tanpa plugin pihak ketiga', 'white');
        $this->line('    [2] Modules + Plugins modul aplikasi + plugin plug-and-play', 'white');
        $this->newLine();

        $answer = strtolower($this->ask('Installation mode (1/2)', '2'));

        if (in_array($answer, ['1', 'modules', 'module', 'minimal'], true)) {
            return self::MODE_MODULES;
        }

        if (in_array($answer, ['2', 'full', 'plugins', 'all'], true)) {
            return self::MODE_FULL;
        }

        $this->error("Unknown choice [{$answer}]. Use 1 (modules only) or 2 (modules + plugins).");

        return null;
    }

    /**
     * Normalize aliases such as minimal/all into the two supported modes.
     */
    protected function normalizeMode(string $mode): ?string
    {
        $mode = strtolower(trim($mode));

        if (in_array($mode, ['modules', 'module', 'minimal', 'modules-only', 'only'], true)) {
            return self::MODE_MODULES;
        }

        if (in_array($mode, ['full', 'plugins', 'all', 'modules+plugins'], true)) {
            return self::MODE_FULL;
        }

        $this->error("Invalid mode [{$mode}]. Valid modes: modules, full.");

        return null;
    }

    /**
     * Create .env when needed, then persist the mode and generate the app key.
     */
    protected function prepareEnvironment(string $mode): bool
    {
        $envPath = $this->app->basePath('.env');

        if (!is_file($envPath)) {
            $examplePath = $this->app->basePath('.env.example');

            if (!is_file($examplePath)) {
                $this->error('.env.example not found — cannot create .env.');

                return false;
            }

            $contents = (string) file_get_contents($examplePath);
            $contents = (string) preg_replace('/^\[TEMPLATE\]\R?/', '', $contents);

            if (file_put_contents($envPath, $contents) === false) {
                $this->error("Unable to write {$envPath}.");

                return false;
            }

            $this->success('Created .env from .env.example');
        }

        $env = (string) file_get_contents($envPath);

        $env = $this->setEnvValue($env, 'PAPASTRO_MODE', $mode);
        $env = $this->setEnvValue($env, 'PLUGINS_ENABLED', $mode === self::MODE_FULL ? 'true' : 'false');
        $env = $this->setEnvValue($env, 'PLUGINS_AUTO_DISCOVER', $mode === self::MODE_FULL ? 'true' : 'false');

        if (!preg_match('/^APP_KEY=(.+)$/m', $env) || trim($this->envValue($env, 'APP_KEY')) === '') {
            $env = $this->setEnvValue($env, 'APP_KEY', 'base64:' . base64_encode(random_bytes(32)));
            $this->success('Generated APP_KEY');
        }

        if (file_put_contents($envPath, $env) === false) {
            $this->error("Unable to write {$envPath}.");

            return false;
        }

        $this->success('Saved installation mode to .env');

        return true;
    }

    /**
     * Create the directories the selected mode needs.
     */
    protected function prepareDirectories(string $mode): void
    {
        $modulesPath = $this->app->appPath('Modules');
        $this->mkdir($modulesPath);
        $this->touch($modulesPath . DIRECTORY_SEPARATOR . '.gitkeep');

        if ($mode === self::MODE_FULL) {
            $pluginsPath = $this->app->pluginPath();
            $this->mkdir($pluginsPath);
            $this->touch($pluginsPath . DIRECTORY_SEPARATOR . '.gitkeep');
            $this->success('Prepared app/Modules and plugins/');
        } else {
            $this->success('Prepared app/Modules (plugins/ is ignored)');
        }

        foreach (['cache/views', 'cache/routes', 'cache/config', 'logs', 'sessions'] as $dir) {
            $this->mkdir($this->app->storagePath($dir));
        }
    }

    /**
     * Drop the cached config so the new mode applies on the next command.
     */
    protected function clearCachedConfiguration(): void
    {
        $cachedConfig = $this->app->storagePath('cache/config/config.php');

        if (is_file($cachedConfig)) {
            unlink($cachedConfig);
            $this->success('Cleared cached configuration');
        }
    }

    /**
     * Read a value from raw .env contents.
     */
    protected function envValue(string $contents, string $key): string
    {
        $pattern = '/^' . preg_quote($key, '/') . '=(.*)$/m';

        if (preg_match($pattern, $contents, $matches)) {
            return trim($matches[1], " \t\n\r\0\x0B\"'");
        }

        return '';
    }

    /**
     * Set or append a key in raw .env contents.
     */
    protected function setEnvValue(string $contents, string $key, string $value): string
    {
        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

        if (preg_match($pattern, $contents)) {
            return (string) preg_replace($pattern, $key . '=' . $value, $contents);
        }

        return rtrim($contents) . PHP_EOL . $key . '=' . $value . PHP_EOL;
    }

    /**
     * Only prompt when a real terminal is attached.
     */
    protected function isInteractive(): bool
    {
        if (function_exists('stream_isatty')) {
            return @stream_isatty(STDIN);
        }

        if (function_exists('posix_isatty')) {
            return @posix_isatty(STDIN);
        }

        return false;
    }

    /**
     * Create an empty placeholder file when it does not exist.
     */
    protected function touch(string $path): void
    {
        if (!file_exists($path)) {
            file_put_contents($path, '');
        }
    }
}
