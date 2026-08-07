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

namespace Papastro\Console;

use Papastro\Foundation\Application;

/**
 * Base Command class for all Papastro CLI commands.
 */
abstract class Command
{
    protected Application $app;
    protected Kernel $console;

    public function __construct(Application $app, Kernel $console)
    {
        $this->app     = $app;
        $this->console = $console;
    }

    /**
     * Execute the command. Must be implemented by each command.
     */
    abstract public function execute(array $args): int;

    /**
     * Output a success message.
     */
    protected function success(string $message): void
    {
        $this->console->success($message);
    }

    /**
     * Output an error message.
     */
    protected function error(string $message): void
    {
        $this->console->error($message);
    }

    /**
     * Output an info message.
     */
    protected function info(string $message): void
    {
        $this->console->info($message);
    }

    /**
     * Output a warning message.
     */
    protected function warn(string $message): void
    {
        $this->console->warn($message);
    }

    /**
     * Output a plain line.
     */
    protected function line(string $message, string $color = 'default'): void
    {
        $this->console->output($message, $color);
    }

    /**
     * Output a blank line.
     */
    protected function newLine(): void
    {
        $this->console->output('');
    }

    /**
     * Ask a question and get input from the user.
     */
    protected function ask(string $question, string $default = ''): string
    {
        echo "\033[36m  ? {$question}" . ($default ? " [{$default}]" : '') . ": \033[0m";
        $answer = trim(fgets(STDIN));
        return $answer === '' ? $default : $answer;
    }

    /**
     * Confirm a yes/no question.
     */
    protected function confirm(string $question, bool $default = true): bool
    {
        $hint = $default ? '[Y/n]' : '[y/N]';
        echo "\033[36m  ? {$question} {$hint}: \033[0m";
        $answer = strtolower(trim(fgets(STDIN)));

        if ($answer === '') return $default;
        return in_array($answer, ['y', 'yes', '1', 'true']);
    }

    /**
     * Get a named option from args (--name=value or --name value).
     */
    protected function option(array $args, string $name, mixed $default = null): mixed
    {
        $prefix = "--{$name}=";
        foreach ($args as $i => $arg) {
            if (str_starts_with($arg, $prefix)) {
                return substr($arg, strlen($prefix));
            }
            if ($arg === "--{$name}" && isset($args[$i + 1])) {
                return $args[$i + 1];
            }
            if ($arg === "--{$name}") {
                return true;
            }
        }
        return $default;
    }

    /**
     * Check if a flag is present.
     */
    protected function hasFlag(array $args, string $flag): bool
    {
        return in_array("--{$flag}", $args) || in_array("-" . $flag[0], $args);
    }

    /**
     * Get positional argument by index.
     */
    protected function argument(array $args, int $index, mixed $default = null): mixed
    {
        $positional = array_values(array_filter($args, fn ($a) => !str_starts_with($a, '-')));
        return $positional[$index] ?? $default;
    }

    /**
     * Create directories recursively.
     */
    protected function mkdir(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Write content to a file, optionally asking for confirmation if exists.
     */
    protected function writeFile(string $path, string $content, bool $force = false): bool
    {
        if (file_exists($path) && !$force) {
            $this->warn("File already exists: {$path}");
            return false;
        }

        $this->mkdir(dirname($path));
        file_put_contents($path, $content);
        $this->success("Created: {$path}");
        return true;
    }

    /**
     * Get the standard Papastro file header comment.
     */
    protected function getHeader(): string
    {
        return <<<'PHP'
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

PHP;
    }

    /**
     * Get the standard .pstro file header comment.
     */
    protected function getPstroHeader(): string
    {
        return <<<'PSTRO'
<!--
  Papastro Framework v1.1.0 — created by kangpcode (Dhafa Nazula Permadi)
  Free to use & extend. This watermark must remain in source code.
-->
PSTRO;
    }

    /**
     * Get the standard JS/TS island header comment.
     */
    protected function getIslandHeader(): string
    {
        return <<<'JS'
/*
 * Papastro Framework v1.1.0 — created by kangpcode (Dhafa Nazula Permadi)
 * Free to use & extend. This watermark must remain in source code.
 */
JS;
    }
}
