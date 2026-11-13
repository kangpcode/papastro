<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v4.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Papastro\Foundation\Application;
use Papastro\Console\Kernel;

class BenchmarkCommandTest extends TestCase
{
    protected Application $app;

    protected function setUp(): void
    {
        $this->app = require __DIR__ . '/../../bootstrap/app.php';
    }

    public function testBenchmarkCommandExecutesSuccessfully(): void
    {
        $kernel = new Kernel($this->app);

        // Run with small request count for fast unit test
        ob_start();
        $code = $kernel->handle(['papastro', 'benchmark', '--requests=100']);
        $output = ob_get_clean();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('PAPASTRO v4.0.0 HIGH-CONCURRENCY LIVE BENCHMARK', $output);
        $this->assertStringContainsString('Throughput (Single Core)', $output);
        $this->assertStringContainsString('Zero Leaks', $output);
    }
}
