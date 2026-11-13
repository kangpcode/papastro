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
use Papastro\Http\Request;
use Papastro\Http\Response;
use Papastro\Http\Rpc\RpcGateway;
use Papastro\Security\DdosShield;

class Version5FeaturesTest extends TestCase
{
    protected Application $app;

    protected function setUp(): void
    {
        $this->app = require __DIR__ . '/../../bootstrap/app.php';
        DdosShield::resetState();
    }

    public function testVersionConstantIsFive(): void
    {
        $this->assertSame('5.0.0', Application::VERSION);
        $this->assertSame('5.0.0', $this->app->version());
    }

    public function testDatabaseUpsertAndCursorPagination(): void
    {
        $db = new \Papastro\ORM\Connection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);

        // Create temporary test table
        $db->statement("DROP TABLE IF EXISTS v5_products");
        $db->statement("CREATE TABLE v5_products (id INTEGER PRIMARY KEY, sku TEXT UNIQUE, name TEXT, price INTEGER)");

        $builder = new \Papastro\ORM\QueryBuilder($db, 'v5_products');

        // Test upsert insert
        $affected = $builder->upsert([
            ['sku' => 'PROD-1', 'name' => 'Widget A', 'price' => 100],
            ['sku' => 'PROD-2', 'name' => 'Widget B', 'price' => 200],
        ], 'sku', ['name', 'price']);

        $this->assertGreaterThanOrEqual(1, $affected);

        // Test upsert update
        $affected2 = $builder->upsert([
            ['sku' => 'PROD-1', 'name' => 'Widget A Updated', 'price' => 150],
        ], 'sku', ['name', 'price']);

        $updated = (new \Papastro\ORM\QueryBuilder($db, 'v5_products'))->where('sku', 'PROD-1')->first();
        $this->assertSame('Widget A Updated', $updated['name']);
        $this->assertEquals(150, $updated['price']);

        // Insert additional for cursor pagination
        for ($i = 3; $i <= 10; $i++) {
            $builder->insert(['sku' => "PROD-{$i}", 'name' => "Widget {$i}", 'price' => $i * 10]);
        }

        // Test cursorPaginate first page
        $page1 = (new \Papastro\ORM\QueryBuilder($db, 'v5_products'))->cursorPaginate(4, 'id');
        $this->assertCount(4, $page1['data']);
        $this->assertTrue($page1['has_more']);
        $this->assertNotNull($page1['next_cursor']);

        // Test cursorPaginate second page
        $page2 = (new \Papastro\ORM\QueryBuilder($db, 'v5_products'))->cursorPaginate(4, 'id', $page1['next_cursor']);
        $this->assertCount(4, $page2['data']);
        $this->assertNotSame($page1['data'][0]['id'], $page2['data'][0]['id']);

        // Clean up
        $db->statement("DROP TABLE IF EXISTS v5_products");
    }

    public function testPasRpcGatewayDispatchesTypedActions(): void
    {
        $gateway = new RpcGateway($this->app);

        // Create a test controller class dynamically
        $controllerClass = new class {
            public function calculate(int $a, int $b, string $operation = 'add'): array
            {
                $val = $operation === 'multiply' ? ($a * $b) : ($a + $b);
                return ['result' => $val, 'operation' => $operation];
            }
        };

        $controllerName = get_class($controllerClass);

        $request = new Request('POST', '/rpc/calc', [], [], [], [], [], ['content-type' => 'application/json']);
        $request->setRawBody(json_encode([
            'method' => 'calculate',
            'params' => ['a' => 10, 'b' => 5, 'operation' => 'multiply'],
        ]));

        $response = $gateway->dispatch($request, $controllerName);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame(50, $data['result']['result']);
        $this->assertSame('multiply', $data['result']['operation']);
    }

    public function testDdosShieldRateLimiting(): void
    {
        $shield = new DdosShield(maxRequests: 5, decaySeconds: 60, banThreshold: 10, banDuration: 300);

        $request = new Request('GET', '/api/test', [], [], [
            'REMOTE_ADDR' => '192.168.1.100',
        ]);

        $next = fn($req) => Response::json(['status' => 'ok']);

        // First 5 requests should pass
        for ($i = 0; $i < 5; $i++) {
            $res = $shield->handle($request, $next);
            $this->assertSame(200, $res->getStatusCode());
        }

        // 6th request should be throttled (429)
        $res6 = $shield->handle($request, $next);
        $this->assertSame(429, $res6->getStatusCode());
        $data = json_decode($res6->getContent(), true);
        $this->assertSame('Too Many Requests', $data['error']);
    }
}
