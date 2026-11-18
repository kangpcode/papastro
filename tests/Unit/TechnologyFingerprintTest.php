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

use Papastro\Foundation\Application;
use Papastro\Http\Request;
use Papastro\Http\Response;
use Papastro\Http\Rpc\RpcGateway;
use Papastro\Island\IslandRenderer;
use Papastro\View\Compiler;
use PHPUnit\Framework\TestCase;

class TechnologyFingerprintTest extends TestCase
{
    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(__DIR__ . '/../../');
    }

    public function testFrameworkVersionIsDynamicAndAuthoritative(): void
    {
        $version = Application::getFrameworkVersion();
        $this->assertSame(Application::VERSION, $version);
        $this->assertSame($version, papastro_version());
        $this->assertSame($version, $this->app->version());
    }

    public function testResponseContainsXPoweredByHeaderByDefault(): void
    {
        $response = Response::make('Hello World');
        $expected = 'Papastro/' . Application::getFrameworkVersion();

        $this->assertTrue($response->hasHeader('X-Powered-By'));
        $this->assertSame($expected, $response->getHeader('x-powered-by'));
        $this->assertSame($expected, $response->getHeader('X-POWERED-BY'));

        // Case-insensitive removal
        $response->withoutHeader('x-powered-by');
        $this->assertFalse($response->hasHeader('X-Powered-By'));
        $this->assertNull($response->getHeader('X-Powered-By'));
    }

    public function testCompilerInjectsMetaGeneratorInsideHeadTag(): void
    {
        $compiler = new Compiler($this->app);

        $template = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Test Page</title>
</head>
<body>
    <h1>Welcome</h1>
</body>
</html>
HTML;

        $compiled = $compiler->compileString($template);
        $version = Application::getFrameworkVersion();

        $this->assertStringContainsString('content="Papastro v<?php echo \Papastro\Foundation\Application::getFrameworkVersion(); ?>"', $compiled);
        $this->assertStringContainsString('Application::shouldExposeFramework()', $compiled);

        // Verify evaluated output
        ob_start();
        eval('?>' . $compiled);
        $evaluated = ob_get_clean();

        $this->assertStringContainsString('<meta name="generator" content="Papastro v' . $version . '" />', $evaluated);
    }

    public function testCompilerDoesNotDuplicateExistingMetaGenerator(): void
    {
        $compiler = new Compiler($this->app);

        $template = <<<'HTML'
<html>
<head>
    <meta name="generator" content="Custom Generator" />
</head>
<body></body>
</html>
HTML;

        $compiled = $compiler->compileString($template);
        $this->assertSame(1, substr_count($compiled, 'name="generator"'));
        $this->assertStringContainsString('Custom Generator', $compiled);
    }

    public function testIslandRendererGeneratesDomFingerprintAttributes(): void
    {
        ob_start();
        IslandRenderer::renderPlaceholder('test-island-1', 'Blog/Comments', 'visible', ['postId' => 42]);
        $output = ob_get_clean();

        $version = Application::getFrameworkVersion();

        $this->assertStringContainsString('data-papastro-island="Blog/Comments"', $output);
        $this->assertStringContainsString('data-pstro-island="Blog/Comments"', $output);
        $this->assertStringContainsString('data-papastro-v="' . $version . '"', $output);
        $this->assertStringContainsString('data-strategy="visible"', $output);
        $this->assertStringContainsString('papastro-island', $output);
    }

    public function testIslandRendererGeneratesHydrationScriptWithGlobalWindowFingerprint(): void
    {
        $script = IslandRenderer::generateHydrationScript();
        $version = Application::getFrameworkVersion();

        $this->assertStringContainsString('window.__PAPASTRO__ = window.__PAPASTRO__ ||', $script);
        $this->assertStringContainsString('version: version', $script);
        $this->assertStringContainsString('version = "' . $version . '"', $script);
        $this->assertStringContainsString('window.Papastro.version = version;', $script);
        $this->assertStringContainsString('[data-papastro-island], [data-pstro-island]', $script);
    }

    public function testRpcGatewayExposesClientScriptWithGlobalWindowFingerprint(): void
    {
        $script = RpcGateway::clientScript('/api/rpc');
        $version = Application::getFrameworkVersion();

        $this->assertStringContainsString('window.__PAPASTRO__', $script);
        $this->assertStringContainsString('version = "' . $version . '"', $script);
        $this->assertStringContainsString('window.pasrpc = async function', $script);
    }

    public function testRpcGatewayDispatchServesClientJavascriptWhenRequested(): void
    {
        $gateway = new RpcGateway($this->app);
        $request = new Request('GET', '/api/rpc', ['client' => '1']);

        // Dummy class
        $dummyClass = get_class(new class {});
        $response = $gateway->dispatch($request, $dummyClass);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/javascript', (string) $response->getHeader('Content-Type'));
        $this->assertStringContainsString('window.__PAPASTRO__', (string) $response->getContent());
        $this->assertStringContainsString('Papastro/' . Application::getFrameworkVersion(), (string) $response->getHeader('X-Powered-By'));
    }

    public function testExposeFrameworkConfigToggleCanConcealFingerprint(): void
    {
        // Test with PAPASTRO_EXPOSE=false simulated
        putenv('PAPASTRO_EXPOSE=false');
        $_ENV['PAPASTRO_EXPOSE'] = 'false';

        $this->assertFalse(Application::shouldExposeFramework());

        // New response without framework exposure
        $response = Response::make('Secured Content');
        $this->assertNull($response->getHeader('X-Powered-By'));

        ob_start();
        IslandRenderer::renderPlaceholder('test-island-2', 'Blog/Comments', 'idle');
        $output = ob_get_clean();

        // Fingerprint tags should be concealed
        $this->assertStringNotContainsString('data-papastro-v=', $output);
        $this->assertStringNotContainsString('data-pstro-island=', $output);

        // Reset env
        putenv('PAPASTRO_EXPOSE=true');
        $_ENV['PAPASTRO_EXPOSE'] = 'true';
        $this->assertTrue(Application::shouldExposeFramework());
    }
}
