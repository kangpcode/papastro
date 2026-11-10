<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Papastro\Foundation\Application;

abstract class TestCase extends BaseTestCase
{
    protected Application $app;

    /** Boot a fresh application (isolated sqlite + storage) for feature tests. */
    protected function bootApp(array $env = []): Application
    {
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['APP_DEBUG'] = 'false';
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_ENV['CACHE_DRIVER'] = 'array';
        foreach ($env as $k => $v) {
            $_ENV[$k] = $v;
        }
        $this->app = require dirname(__DIR__) . '/bootstrap/app.php';
        $this->app->make(\Papastro\Console\Kernel::class); // bootstraps config + providers
        return $this->app;
    }
}
