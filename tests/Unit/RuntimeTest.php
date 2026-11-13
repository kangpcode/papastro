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

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Papastro\Foundation\Application;
use Papastro\Runtime\StateResetter;
use Papastro\Runtime\FileWatcher;

class RuntimeTest extends TestCase
{
    public function test_state_resetter_clears_scoped_and_request_instances(): void
    {
        $app = new Application(dirname(__DIR__, 2));

        // Bind scoped instance
        $app->scoped('test.scoped', function () {
            return new \stdClass();
        });

        $instance1 = $app->make('test.scoped');
        $this->assertSame($instance1, $app->make('test.scoped'));

        // Bind mock request
        $app->instance('request', new \stdClass());
        $this->assertTrue($app->bound('request'));

        // Reset state
        StateResetter::reset($app);

        // Scoped instance should be cleared and re-instantiated
        $instance2 = $app->make('test.scoped');
        $this->assertNotSame($instance1, $instance2);

        // Request instance should be forgotten
        $this->assertFalse($app->resolved('request'));
    }

    public function test_custom_resetter_callback(): void
    {
        $app = new Application(dirname(__DIR__, 2));
        $called = false;

        StateResetter::register(function ($application) use (&$called, $app) {
            $called = true;
        });

        StateResetter::reset($app);
        $this->assertTrue($called);
    }

    public function test_file_watcher_detects_changes(): void
    {
        $tmpDir = sys_get_temp_dir() . '/papastro_watcher_test_' . uniqid();
        mkdir($tmpDir, 0755, true);
        $testFile = $tmpDir . '/Test.php';
        file_put_contents($testFile, '<?php echo 1;');

        $watcher = new FileWatcher([$tmpDir]);
        $this->assertFalse($watcher->hasChanged());

        // Wait 1 second so filemtime advances
        sleep(1);
        file_put_contents($testFile, '<?php echo 2;');

        $this->assertTrue($watcher->hasChanged());

        @unlink($testFile);
        @rmdir($tmpDir);
    }
}
