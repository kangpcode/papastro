<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v3.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Papastro\Http\Request;
use Papastro\Http\Response;
use App\Http\Middleware\PreventRequestsDuringMaintenance;

class MaintenanceModeTest extends TestCase
{
    protected string $downFile;

    protected function setUp(): void
    {
        $this->downFile = dirname(__DIR__, 2) . '/storage/framework/down';
        if (file_exists($this->downFile)) {
            @unlink($this->downFile);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->downFile)) {
            @unlink($this->downFile);
        }
    }

    public function test_normal_requests_pass_when_maintenance_is_disabled(): void
    {
        $middleware = new PreventRequestsDuringMaintenance();
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/health']);

        $response = $middleware->handle($request, function ($req) {
            return Response::make('OK', 200);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
    }

    public function test_requests_are_blocked_with_503_when_down_file_exists(): void
    {
        file_put_contents($this->downFile, json_encode([
            'secret' => 'super-secret-bypass',
            'message' => 'Custom Maintenance Message',
            'retry' => 120,
        ]));

        $middleware = new PreventRequestsDuringMaintenance();
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/dashboard',
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = $middleware->handle($request, function ($req) {
            return Response::make('Should not reach here', 200);
        });

        $this->assertSame(503, $response->getStatusCode());
        $json = json_decode((string) $response->getContent(), true);
        $this->assertSame('Custom Maintenance Message', $json['message']);
        $this->assertSame(120, $json['retry_after']);
    }

    public function test_bypass_cookie_allows_request_during_maintenance(): void
    {
        $secret = 'my-secret-key';
        file_put_contents($this->downFile, json_encode([
            'secret' => $secret,
            'message' => 'Under Maintenance',
        ]));

        $middleware = new PreventRequestsDuringMaintenance();
        $cookieHash = hash('sha256', $secret);

        $request = new Request([], [], [], ['papastro_maintenance_bypass' => $cookieHash], [], [
            'REQUEST_URI' => '/admin',
        ]);

        $response = $middleware->handle($request, function ($req) {
            return Response::make('Authorized Admin Access', 200);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Authorized Admin Access', $response->getContent());
    }

    public function test_bypass_header_allows_request_during_maintenance(): void
    {
        $secret = 'header-token-123';
        file_put_contents($this->downFile, json_encode([
            'secret' => $secret,
            'message' => 'Under Maintenance',
        ]));

        $middleware = new PreventRequestsDuringMaintenance();
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/api/test',
            'HTTP_X_MAINTENANCE_SECRET' => $secret,
        ]);

        $response = $middleware->handle($request, function ($req) {
            return Response::make('Authorized Via Header', 200);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Authorized Via Header', $response->getContent());
    }
}
