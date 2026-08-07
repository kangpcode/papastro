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

namespace App\Console;

use Papastro\Console\Kernel as BaseKernel;
use Papastro\Foundation\Application;

class Kernel extends BaseKernel
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->bootstrap();
    }

    /**
     * Bootstrap the application for console use.
     * Called in constructor so the app is ready when handle() runs.
     */
    protected function bootstrap(): void
    {
        if (!$this->app->hasBeenBootstrapped()) {
            $this->app->bootstrapWith([
                \Papastro\Foundation\Bootstrap\LoadEnvironmentVariables::class,
                \Papastro\Foundation\Bootstrap\LoadConfiguration::class,
                \Papastro\Foundation\Bootstrap\HandleExceptions::class,
                \Papastro\Foundation\Bootstrap\RegisterProviders::class,
                \Papastro\Foundation\Bootstrap\BootProviders::class,
            ]);
        }
    }
}
