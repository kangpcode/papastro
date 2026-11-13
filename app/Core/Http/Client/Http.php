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

namespace Papastro\Http\Client;

/**
 * PasFetch HTTP Client Facade.
 *
 * Usage:
 *   Http::get('https://api.github.com/users/kangpcode');
 *   Http::withToken('xyz')->asJson()->post('https://api.example.com/items', ['name' => 'Widget']);
 */
class Http
{
    public static function create(): PendingRequest
    {
        return new PendingRequest();
    }

    public static function withHeaders(array $headers): PendingRequest
    {
        return static::create()->withHeaders($headers);
    }

    public static function withToken(string $token, string $type = 'Bearer'): PendingRequest
    {
        return static::create()->withToken($token, $type);
    }

    public static function withBasicAuth(string $username, string $password): PendingRequest
    {
        return static::create()->withBasicAuth($username, $password);
    }

    public static function asJson(): PendingRequest
    {
        return static::create()->asJson();
    }

    public static function asForm(): PendingRequest
    {
        return static::create()->asForm();
    }

    public static function timeout(int $seconds): PendingRequest
    {
        return static::create()->timeout($seconds);
    }

    public static function retry(int $times, int $sleepMs = 100): PendingRequest
    {
        return static::create()->retry($times, $sleepMs);
    }

    public static function get(string $url, array $query = []): Response
    {
        return static::create()->get($url, $query);
    }

    public static function post(string $url, array|string $data = []): Response
    {
        return static::create()->post($url, $data);
    }

    public static function put(string $url, array|string $data = []): Response
    {
        return static::create()->put($url, $data);
    }

    public static function patch(string $url, array|string $data = []): Response
    {
        return static::create()->patch($url, $data);
    }

    public static function delete(string $url, array|string $data = []): Response
    {
        return static::create()->delete($url, $data);
    }

    /**
     * Run multiple HTTP requests concurrently using Pastroutine or cURL multi.
     */
    public static function pool(callable $callback): array
    {
        $requests = [];
        $callback(function (string $key, PendingRequest $request, string $method, string $url, array $data = []) use (&$requests) {
            $requests[$key] = [$request, $method, $url, $data];
        });

        $responses = [];

        // Concurrently dispatch using Pastroutine WaitGroup if available
        if (class_exists(\Papastro\Pastroutine\WaitGroup::class)) {
            $wg = new \Papastro\Pastroutine\WaitGroup();
            foreach ($requests as $key => [$req, $method, $url, $data]) {
                $wg->add(1);
                \Papastro\Pastroutine\pastroutine(function () use (&$responses, $key, $req, $method, $url, $data, $wg) {
                    try {
                        $responses[$key] = $req->send($method, $url, $data);
                    } catch (\Throwable $e) {
                        $responses[$key] = $e;
                    } finally {
                        $wg->done();
                    }
                });
            }
            $wg->wait();
        } else {
            foreach ($requests as $key => [$req, $method, $url, $data]) {
                $responses[$key] = $req->send($method, $url, $data);
            }
        }

        return $responses;
    }
}
