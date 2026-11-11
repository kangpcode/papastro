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

namespace Papastro\Pasway;

/**
 * Pasway — Realtime Broadcasting Engine (Echo / Pusher-compatible).
 */
class Pasway
{
    /**
     * Broadcast an event to one or more channels.
     *
     * @param string|array<string> $channels Channel or array of channels
     * @param string $event Event name
     * @param mixed $data Payload data
     */
    public static function broadcast(string|array $channels, string $event, mixed $data = []): bool
    {
        $channels = is_array($channels) ? $channels : [$channels];
        $payload = [
            'event'    => $event,
            'channels' => $channels,
            'data'     => $data,
            'time'     => microtime(true),
        ];

        $dispatched = false;

        // 1. Try IPC socket or IPC TCP loopback to WebSocket server
        $ipcPort = function_exists('config') ? (int) config('pasway.server.ipc_port', 8081) : 8081;
        $ipcHost = '127.0.0.1';

        $socket = @fsockopen($ipcHost, $ipcPort, $errno, $errstr, 0.2);
        if ($socket) {
            stream_set_timeout($socket, 0, 500000); // 500ms max
            @fwrite($socket, json_encode($payload) . "\n");
            @fclose($socket);
            $dispatched = true;
        }

        // 2. Also record in SSE cache file for Server-Sent Events subscribers
        self::recordSseEvent($payload);

        return $dispatched;
    }

    /**
     * Create or retrieve a public channel broadcaster.
     */
    public static function channel(string $name): Channel
    {
        return new Channel($name);
    }

    /**
     * Create or retrieve a private channel broadcaster.
     */
    public static function private(string $name): Channel
    {
        $name = str_starts_with($name, 'private-') ? $name : 'private-' . $name;
        return new Channel($name);
    }

    /**
     * Create or retrieve a presence channel broadcaster.
     */
    public static function presence(string $name): Channel
    {
        $name = str_starts_with($name, 'presence-') ? $name : 'presence-' . $name;
        return new Channel($name);
    }

    /**
     * Generate an authentication payload for a private or presence channel.
     *
     * @param string $channel Channel name (e.g., 'private-orders.1' or 'presence-room.5')
     * @param string $socketId Client WebSocket socket identifier
     * @param array|null $userData Optional user information for presence channels
     * @return array{auth: string, channel_data?: string}
     */
    public static function auth(string $channel, string $socketId, ?array $userData = null): array
    {
        $appKey = self::getAppKey();
        $appSecret = self::getAppSecret();

        if (str_starts_with($channel, 'presence-')) {
            $userDataJson = json_encode($userData ?? [
                'user_id' => 'guest_' . substr(md5($socketId), 0, 8),
                'user_info' => ['name' => 'Guest'],
            ], JSON_UNESCAPED_SLASHES);

            $signature = hash_hmac('sha256', "{$socketId}:{$channel}:{$userDataJson}", $appSecret);
            return [
                'auth' => "{$appKey}:{$signature}",
                'channel_data' => $userDataJson,
            ];
        }

        $signature = hash_hmac('sha256', "{$socketId}:{$channel}", $appSecret);
        return [
            'auth' => "{$appKey}:{$signature}",
        ];
    }

    /**
     * Verify an HMAC signature sent by a client.
     */
    public static function verifyAuth(string $channel, string $socketId, string $authHeader, ?string $channelData = null): bool
    {
        $appSecret = self::getAppSecret();
        $parts = explode(':', $authHeader, 2);
        if (count($parts) !== 2) {
            return false;
        }

        [$clientKey, $clientSig] = $parts;
        if (!hash_equals(self::getAppKey(), $clientKey)) {
            return false;
        }

        if (str_starts_with($channel, 'presence-') && $channelData !== null) {
            $expectedSig = hash_hmac('sha256', "{$socketId}:{$channel}:{$channelData}", $appSecret);
        } else {
            $expectedSig = hash_hmac('sha256', "{$socketId}:{$channel}", $appSecret);
        }

        return hash_equals($expectedSig, $clientSig);
    }

    /**
     * Get the configured app key.
     */
    public static function getAppKey(): string
    {
        $envKey = getenv('PASWAY_APP_KEY') ?: ($_ENV['PASWAY_APP_KEY'] ?? null);
        if ($envKey) {
            return (string) $envKey;
        }

        if (function_exists('config')) {
            return (string) config('pasway.app_key', 'papastro-realtime-key');
        }

        return 'papastro-realtime-key';
    }

    /**
     * Get the configured app secret.
     */
    public static function getAppSecret(): string
    {
        $envSecret = getenv('PASWAY_APP_SECRET') ?: ($_ENV['PASWAY_APP_SECRET'] ?? null);
        if ($envSecret) {
            return (string) $envSecret;
        }

        if (function_exists('config')) {
            return (string) config('pasway.app_secret', 'papastro-realtime-secret');
        }

        return 'papastro-realtime-secret';
    }

    /**
     * Get the absolute path to the SSE events cache file.
     */
    public static function getSseFilePath(): string
    {
        $app = \Papastro\Foundation\Application::getInstance();
        if ($app !== null) {
            return $app->storagePath('framework/pasway/sse_events.json');
        }

        return dirname(__DIR__, 3) . '/storage/framework/pasway/sse_events.json';
    }

    /**
     * Append an event to the SSE cache file for Server-Sent Events subscribers.
     */
    protected static function recordSseEvent(array $payload): void
    {
        $sseFile = self::getSseFilePath();
        $dir = dirname($sseFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $events = [];
        if (is_file($sseFile)) {
            $events = json_decode((string) @file_get_contents($sseFile), true) ?: [];
        }

        $events[] = $payload;

        // Keep last 100 events to prevent unbounded growth
        if (count($events) > 100) {
            $events = array_slice($events, -100);
        }

        @file_put_contents($sseFile, json_encode($events), LOCK_EX);
    }

    /**
     * Read pending SSE events for specific channels.
     */
    public static function getSseEvents(array $channels, float $since = 0): array
    {
        $sseFile = self::getSseFilePath();
        if (!is_file($sseFile)) {
            return [];
        }

        $events = json_decode((string) @file_get_contents($sseFile), true) ?: [];
        $matched = [];

        foreach ($events as $event) {
            if (($event['time'] ?? 0) <= $since) {
                continue;
            }

            $eventChannels = $event['channels'] ?? [];
            if (!empty(array_intersect($channels, $eventChannels))) {
                $matched[] = $event;
            }
        }

        return $matched;
    }
}
