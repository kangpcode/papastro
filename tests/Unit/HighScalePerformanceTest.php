<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v4.0.0 — created by kangpcode (Dhafa Nazula Permadi)
 *  Free to use & extend. This watermark must remain in source code.
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Papastro\Http\Request;
use Papastro\Http\Response;
use Papastro\Router\Route;
use Papastro\ORM\Connection;
use App\Http\Middleware\FullPageCache;
use App\Http\Middleware\CompressResponse;

class HighScalePerformanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->bootApp();
    }

    public function testFullPageCacheMissHitAnd304Etag(): void
    {
        $middleware = new FullPageCache($this->app->make(\Papastro\Cache\CacheManager::class));

        $req1 = new Request('GET', '/high-scale-page');
        $res1 = $middleware->handle($req1, fn() => Response::html('<h1>High Scale Content</h1>', 200), 60);

        $this->assertSame('MISS', $res1->getHeaders()['X-Papastro-Cache']);
        $this->assertTrue($res1->hasHeader('ETag'));
        $etag = $res1->getHeaders()['ETag'];

        // Second request: Cache HIT
        $req2 = new Request('GET', '/high-scale-page');
        $res2 = $middleware->handle($req2, fn() => Response::html('<h1>High Scale Content</h1>', 200), 60);

        $this->assertSame('HIT', $res2->getHeaders()['X-Papastro-Cache']);
        $this->assertSame('<h1>High Scale Content</h1>', $res2->getContent());

        // Third request with If-None-Match matching ETag: HTTP 304 Not Modified
        $req3 = new Request('GET', '/high-scale-page', [], [], [], [], [], ['if-none-match' => $etag]);
        $res3 = $middleware->handle($req3, fn() => Response::html('<h1>High Scale Content</h1>', 200), 60);

        $this->assertSame(304, $res3->getStatusCode());
        $this->assertSame('', $res3->getContent());
        $this->assertSame('HIT-REVALIDATED', $res3->getHeaders()['X-Papastro-Cache']);
    }

    public function testGzipCompressionMiddleware(): void
    {
        $middleware = new CompressResponse();

        $largeBody = str_repeat('Papastro High Concurrency Framework v4.0.0. ', 100); // ~4.5KB

        // Without Accept-Encoding gzip -> No compression
        $req1 = new Request('GET', '/test');
        $res1 = $middleware->handle($req1, fn() => Response::html($largeBody, 200));
        $this->assertFalse($res1->hasHeader('Content-Encoding'));

        // With Accept-Encoding gzip -> Compressed
        $req2 = new Request('GET', '/test', [], [], [], [], [], ['accept-encoding' => 'gzip, deflate, br']);
        $res2 = $middleware->handle($req2, fn() => Response::html($largeBody, 200));

        $this->assertTrue($res2->hasHeader('Content-Encoding'));
        $this->assertSame('gzip', $res2->getHeaders()['Content-Encoding']);
        $this->assertSame('Accept-Encoding', $res2->getHeaders()['Vary']);
        $this->assertLessThan(strlen($largeBody), strlen((string) $res2->getContent()));
    }

    public function testRouteFluentCacheMacro(): void
    {
        $route = new Route(['GET'], '/cached-route', fn() => 'OK');
        $this->assertEmpty($route->getMiddleware());

        $route->cache(3600);
        $this->assertContains('cache.page:3600', $route->getMiddleware());
    }

    public function testDatabaseReadWriteReplicaRouting(): void
    {
        $tempDir = $this->app->storagePath('testing/rw_' . uniqid());
        $writeDb = $tempDir . '/write.sqlite';
        $readDb = $tempDir . '/read.sqlite';

        $conn = new Connection([
            'driver' => 'sqlite',
            'database' => $writeDb,
            'write' => ['database' => $writeDb],
            'read' => [['database' => $readDb]],
        ]);

        $this->assertInstanceOf(\PDO::class, $conn->getPdo());
        $this->assertInstanceOf(\PDO::class, $conn->getReadPdo());
        $this->assertFileExists($writeDb);
        $this->assertFileExists($readDb);

        $conn->disconnect();
        @unlink($writeDb);
        @unlink($readDb);
        @rmdir($tempDir);
    }
}

