<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v5.0.0 — created by kangpcode (Dhafa Nazula Permadi)
 *  Free to use & extend. This watermark must remain in source code.
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Papastro\Foundation\Application;
use Papastro\Http\StreamResponse;
use Papastro\Http\Response;
use Papastro\Http\Client\Http;
use Papastro\Http\Client\Response as ClientResponse;
use Papastro\Router\Route;
use Papastro\SSG\Prerenderer;
use Papastro\Pascon\Pascon;

class V4FeaturesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->bootApp();
    }

    public function testFrameworkVersionIsFour(): void
    {
        $this->assertSame('4.0.0', Application::VERSION);
        $this->assertSame('4.0.0', PAPASTRO_VERSION);
        $this->assertSame('4.0.0', config('app.version'));
    }

    public function testStreamResponseSseHeadersAndFormat(): void
    {
        $response = Response::sse(function ($emitter) {
            $emitter->event(['message' => 'Hello v4'], 'greeting', 1);
        });

        $this->assertInstanceOf(StreamResponse::class, $response);
        $this->assertSame('text/event-stream; charset=UTF-8', $response->getHeaders()['Content-Type']);
        $this->assertSame('no-cache, no-transform', $response->getHeaders()['Cache-Control']);
        $this->assertSame('keep-alive', $response->getHeaders()['Connection']);
        $this->assertSame('no', $response->getHeaders()['X-Accel-Buffering']);
    }

    public function testHttpClientResponseHelpers(): void
    {
        $json = json_encode(['status' => 'success', 'data' => ['user' => 'kangpcode']]);
        $clientResponse = new ClientResponse(200, $json, ['Content-Type' => 'application/json']);

        $this->assertTrue($clientResponse->ok());
        $this->assertTrue($clientResponse->successful());
        $this->assertFalse($clientResponse->failed());
        $this->assertSame('kangpcode', $clientResponse->json('data.user'));
        $this->assertSame('application/json', $clientResponse->header('Content-Type'));
    }

    public function testRoutePrerenderFlag(): void
    {
        $route = new Route(['GET'], '/about-us', fn() => 'About Page');
        $this->assertFalse($route->isPrerender());

        $route->prerender();
        $this->assertTrue($route->isPrerender());
    }

    public function testExpandedPasconIcons(): void
    {
        $icons = ['stream', 'server', 'cloud', 'git', 'folder', 'file', 'bell', 'calendar', 'lock', 'unlock', 'sun', 'moon', 'download', 'upload', 'filter'];

        foreach ($icons as $icon) {
            $svg = Pascon::svg($icon, ['size' => 18]);
            $this->assertStringContainsString('<svg', $svg);
            $this->assertStringContainsString('width="18"', $svg);
            $this->assertStringContainsString("pascon-{$icon}", $svg);
        }
    }
}
