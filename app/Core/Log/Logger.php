<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v5.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Log;

use Papastro\Foundation\Application;

/**
 * Logger — Structured application logger supporting PSR-3 log levels.
 */
class Logger
{
    protected Application $app;
    protected string $logPath;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->logPath = $app->storagePath('logs/papastro.log');
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('EMERGENCY', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('ALERT', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('NOTICE', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * Write a formatted log entry to storage/logs/papastro.log.
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $env = $this->app->has('config') ? $this->app->environment() : 'production';
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';

        $line = "[{$timestamp}] {$env}.{$level}: {$message}{$contextStr}" . PHP_EOL;
        @file_put_contents($this->logPath, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get the log file path.
     */
    public function getLogPath(): string
    {
        return $this->logPath;
    }
}
