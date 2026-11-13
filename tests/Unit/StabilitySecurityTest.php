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
use Papastro\Router\Route;
use Papastro\Http\Request;
use Papastro\Http\Response;
use Papastro\View\ViewEngine;
use Papastro\ORM\Connection;
use Papastro\Security\Security;

class DummyInvokableController
{
    public function __invoke(): string
    {
        return 'Invokable Success';
    }
}

class StabilitySecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->bootApp();
    }

    public function testInvokableControllerRouteExecution(): void
    {
        $route = new Route(['GET'], '/invokable-test', DummyInvokableController::class);
        $route->setContainer($this->app);

        $request = new Request('GET', '/invokable-test');
        $response = $route->run($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('Invokable Success', $response->getContent());
    }

    public function testViewEnginePathTraversalProtection(): void
    {
        $viewEngine = new ViewEngine($this->app);
        $path = $viewEngine->resolvePath('../../../etc/passwd');

        $this->assertStringNotContainsString('../', $path);
        $this->assertStringContainsString('resources/views', str_replace('\\', '/', $path));
    }

    public function testViewEngineProductionCaching(): void
    {
        $viewEngine = new ViewEngine($this->app);
        
        $tempSource = $this->app->storagePath('cache/test_view.pstro');
        $tempCompiled = $this->app->storagePath('cache/test_view_compiled.php');

        file_put_contents($tempSource, '<h1>Hello</h1>');
        file_put_contents($tempCompiled, '<?php echo "<h1>Hello</h1>";');

        // Set modification time on compiled to be older than source
        touch($tempCompiled, time() - 3600);
        touch($tempSource, time());

        // In test/local environment, expired is true because source is newer
        $this->assertTrue($viewEngine->isExpired($tempSource, $tempCompiled));

        // Clean up
        @unlink($tempSource);
        @unlink($tempCompiled);
    }

    public function testSqliteAutoProvisioning(): void
    {
        $tempDir = $this->app->storagePath('testing/db_' . uniqid());
        $tempDb = $tempDir . '/auto_test.sqlite';

        $this->assertFileDoesNotExist($tempDb);

        $conn = new Connection([
            'driver' => 'sqlite',
            'database' => $tempDb,
        ]);

        $pdo = $conn->getPdo();
        $this->assertInstanceOf(\PDO::class, $pdo);
        $this->assertFileExists($tempDb);

        $conn->disconnect();
        @unlink($tempDb);
        @rmdir($tempDir);
    }

    public function testSecurityCompareTokensTimingSafe(): void
    {
        $token = bin2hex(random_bytes(16));
        $this->assertTrue(Security::compareTokens($token, $token));
        $this->assertFalse(Security::compareTokens($token, 'wrong-token'));
    }

    public function testSecuritySanitizeSqlIdentifier(): void
    {
        $this->assertSame('users.id', Security::sanitizeSqlIdentifier('users.id'));
        $this->assertSame('posts.title as post_title', Security::sanitizeSqlIdentifier('posts.title as post_title'));

        $this->expectException(\InvalidArgumentException::class);
        Security::sanitizeSqlIdentifier('users; DROP TABLE users;--');
    }
}

