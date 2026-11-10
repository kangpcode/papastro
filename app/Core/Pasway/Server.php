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
 * High-Performance RFC 6455 WebSocket & Realtime Server.
 * Supports public, private, and presence channels with Pusher/Echo compatibility.
 */
class Server
{
    protected string $host;
    protected int $port;
    protected int $ipcPort;

    /** @var resource|null */
    protected $wsServer = null;

    /** @var resource|null */
    protected $ipcServer = null;

    /** @var array<int, resource> Client sockets indexed by int resource ID */
    protected array $clients = [];

    /** @var array<int, array> Client metadata indexed by int resource ID */
    protected array $clientMeta = [];

    /** @var array<string, array<int, bool>> Subscriptions: channel => [clientId => true] */
    protected array $channelSubscribers = [];

    /** @var array<string, array<string, array>> Presence members: channel => [userId => memberData] */
    protected array $presenceMembers = [];

    protected bool $running = false;

    public function __construct(string $host = '0.0.0.0', int $port = 8080, int $ipcPort = 8081)
    {
        $this->host = $host;
        $this->port = $port;
        $this->ipcPort = $ipcPort;
    }

    /**
     * Start the WebSocket server loop.
     */
    public function start(): void
    {
        $wsUri = "tcp://{$this->host}:{$this->port}";
        $ipcUri = "tcp://127.0.0.1:{$this->ipcPort}";

        $context = stream_context_create([
            'socket' => [
                'so_reuseport' => 1,
                'backlog' => 1024,
            ],
        ]);

        $this->wsServer = @stream_socket_server($wsUri, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
        if (!$this->wsServer) {
            throw new \RuntimeException("Failed to bind WebSocket server on {$wsUri}: [{$errno}] {$errstr}");
        }
        stream_set_blocking($this->wsServer, false);

        $this->ipcServer = @stream_socket_server($ipcUri, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
        if (!$this->ipcServer) {
            @fclose($this->wsServer);
            throw new \RuntimeException("Failed to bind IPC broadcast server on {$ipcUri}: [{$errno}] {$errstr}");
        }
        stream_set_blocking($this->ipcServer, false);

        $this->running = true;

        echo "\033[35m";
        echo "  ⚡ Pasway Realtime WebSocket Server (v3.0.0)\n";
        echo "  @author kangpcode (Dhafa Nazula Permadi)\n";
        echo "\033[0m";
        echo "  📡 WebSocket Listening on : ws://{$this->host}:{$this->port}\n";
        echo "  🔗 Broadcast IPC Listening: tcp://127.0.0.1:{$this->ipcPort}\n";
        echo "  Press Ctrl+C to stop.\n\n";

        $this->registerSignalHandlers();

        while ($this->running) {
            $read = array_merge([$this->wsServer, $this->ipcServer], $this->clients);
            $write = null;
            $except = null;

            $numChanged = @stream_select($read, $write, $except, 1, 0);
            if ($numChanged === false || $numChanged === 0) {
                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }
                continue;
            }

            foreach ($read as $socket) {
                if ($socket === $this->wsServer) {
                    $this->acceptWebSocketClient();
                } elseif ($socket === $this->ipcServer) {
                    $this->acceptIpcBroadcast();
                } else {
                    $this->handleClientData($socket);
                }
            }

            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
        }

        $this->stop();
    }

    /**
     * Stop the server and close all client connections.
     */
    public function stop(): void
    {
        $this->running = false;

        foreach ($this->clients as $socket) {
            $this->closeClient($socket);
        }

        if (is_resource($this->wsServer)) {
            @fclose($this->wsServer);
            $this->wsServer = null;
        }

        if (is_resource($this->ipcServer)) {
            @fclose($this->ipcServer);
            $this->ipcServer = null;
        }
    }

    /**
     * Accept a new incoming WebSocket client.
     */
    protected function acceptWebSocketClient(): void
    {
        $client = @stream_socket_accept($this->wsServer, 0);
        if (!$client) {
            return;
        }

        stream_set_blocking($client, false);
        $id = (int) $client;
        $socketId = sprintf('%d.%d', mt_rand(1000000, 9999999), mt_rand(1000000, 9999999));

        $this->clients[$id] = $client;
        $this->clientMeta[$id] = [
            'id' => $id,
            'socket_id' => $socketId,
            'handshake' => false,
            'buffer' => '',
            'channels' => [],
            'presence' => [],
        ];
    }

    /**
     * Accept an incoming IPC broadcast message from a PHP worker.
     */
    protected function acceptIpcBroadcast(): void
    {
        $ipcClient = @stream_socket_accept($this->ipcServer, 0);
        if (!$ipcClient) {
            return;
        }

        $data = '';
        while (!feof($ipcClient)) {
            $chunk = @fread($ipcClient, 8192);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $data .= $chunk;
        }
        @fclose($ipcClient);

        $payload = json_decode(trim($data), true);
        if (!is_array($payload) || !isset($payload['event'], $payload['channels'])) {
            return;
        }

        $event = (string) $payload['event'];
        $channels = (array) $payload['channels'];
        $data = $payload['data'] ?? [];

        $this->broadcastToChannels($channels, $event, $data);
    }

    /**
     * Read and handle data from a connected WebSocket client.
     */
    protected function handleClientData($client): void
    {
        $id = (int) $client;
        if (!isset($this->clientMeta[$id])) {
            return;
        }

        $meta = &$this->clientMeta[$id];
        $chunk = @fread($client, 8192);

        if ($chunk === false || $chunk === '') {
            $this->closeClient($client);
            return;
        }

        $meta['buffer'] .= $chunk;

        // Step 1: Perform WebSocket Handshake
        if (!$meta['handshake']) {
            if (str_contains($meta['buffer'], "\r\n\r\n")) {
                $this->performHandshake($client, $meta);
                $meta['buffer'] = '';
            }
            return;
        }

        // Step 2: Decode RFC 6455 frames
        while (strlen($meta['buffer']) >= 2) {
            $frame = $this->decodeFrame($meta['buffer']);
            if ($frame === null) {
                // Incomplete frame, wait for more data
                break;
            }

            $meta['buffer'] = substr($meta['buffer'], $frame['bytes_consumed']);

            if ($frame['opcode'] === 0x8) {
                // Close frame
                $this->closeClient($client);
                return;
            } elseif ($frame['opcode'] === 0x9) {
                // Ping frame -> send Pong
                $this->sendFrame($client, $this->encodeFrame($frame['payload'], 0xA));
            } elseif ($frame['opcode'] === 0x1) {
                // Text frame
                $this->handleClientMessage($client, $frame['payload']);
            }
        }
    }

    /**
     * Complete RFC 6455 handshake.
     */
    protected function performHandshake($client, array &$meta): void
    {
        $headers = [];
        $lines = explode("\r\n", $meta['buffer']);
        foreach ($lines as $line) {
            if (str_contains($line, ':')) {
                [$k, $v] = explode(':', $line, 2);
                $headers[strtolower(trim($k))] = trim($v);
            }
        }

        $key = $headers['sec-websocket-key'] ?? null;
        if (!$key) {
            $this->closeClient($client);
            return;
        }

        $acceptKey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        $response = "HTTP/1.1 101 Switching Protocols\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept: {$acceptKey}\r\n\r\n";

        @fwrite($client, $response);
        $meta['handshake'] = true;

        // Send connection established event
        $initMessage = json_encode([
            'event' => 'pusher:connection_established',
            'data'  => json_encode([
                'socket_id'        => $meta['socket_id'],
                'activity_timeout' => 120,
            ]),
        ]);
        $this->sendText($client, $initMessage);
    }

    /**
     * Handle incoming parsed text messages from client (Pusher protocol).
     */
    protected function handleClientMessage($client, string $rawMessage): void
    {
        $id = (int) $client;
        $meta = &$this->clientMeta[$id];
        $msg = json_decode($rawMessage, true);

        if (!is_array($msg) || !isset($msg['event'])) {
            return;
        }

        $event = (string) $msg['event'];
        $data = $msg['data'] ?? [];
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }

        switch ($event) {
            case 'pusher:ping':
                $this->sendText($client, json_encode(['event' => 'pusher:pong', 'data' => []]));
                break;

            case 'pusher:subscribe':
                $channel = $data['channel'] ?? '';
                if (!$channel) break;

                // Validate authentication for private and presence channels
                if (str_starts_with($channel, 'private-') || str_starts_with($channel, 'presence-')) {
                    $authHeader = $data['auth'] ?? '';
                    $channelData = $data['channel_data'] ?? null;

                    if (!Pasway::verifyAuth($channel, $meta['socket_id'], $authHeader, $channelData)) {
                        $this->sendText($client, json_encode([
                            'event'   => 'pusher:error',
                            'data'    => ['message' => 'Unauthorized channel subscription'],
                            'channel' => $channel,
                        ]));
                        return;
                    }
                }

                $meta['channels'][$channel] = true;
                $this->channelSubscribers[$channel][$id] = true;

                // Presence channel handling
                $presenceData = null;
                if (str_starts_with($channel, 'presence-')) {
                    $userData = json_decode((string) ($data['channel_data'] ?? '{}'), true) ?: [];
                    $userId = (string) ($userData['user_id'] ?? 'user_' . $id);
                    $userInfo = $userData['user_info'] ?? [];

                    $meta['presence'][$channel] = ['user_id' => $userId, 'user_info' => $userInfo];
                    $this->presenceMembers[$channel][$userId] = $userInfo;

                    // Notify existing members
                    $this->broadcastToChannel($channel, 'pusher_internal:member_added', [
                        'user_id'   => $userId,
                        'user_info' => $userInfo,
                    ], $id);

                    // Prepare existing members list for the subscribing client
                    $presenceData = [
                        'presence' => [
                            'ids'   => array_keys($this->presenceMembers[$channel]),
                            'hash'  => $this->presenceMembers[$channel],
                            'count' => count($this->presenceMembers[$channel]),
                        ],
                    ];
                }

                // Confirm subscription
                $this->sendText($client, json_encode([
                    'event'   => 'pusher_internal:subscription_succeeded',
                    'channel' => $channel,
                    'data'    => $presenceData ? json_encode($presenceData) : '{}',
                ]));
                break;

            case 'pusher:unsubscribe':
                $channel = $data['channel'] ?? '';
                $this->leaveChannel($id, $channel);
                break;

            default:
                // Client-triggered event (whisper) e.g., 'client-typing'
                if (str_starts_with($event, 'client-')) {
                    $channel = $msg['channel'] ?? '';
                    if ($channel && isset($meta['channels'][$channel])) {
                        $this->broadcastToChannel($channel, $event, $data, $id);
                    }
                }
                break;
        }
    }

    /**
     * Broadcast an event to multiple channels.
     */
    public function broadcastToChannels(array $channels, string $event, mixed $data): void
    {
        foreach ($channels as $channel) {
            $this->broadcastToChannel($channel, $event, $data);
        }
    }

    /**
     * Broadcast an event to a single channel.
     */
    public function broadcastToChannel(string $channel, string $event, mixed $data, ?int $excludeClientId = null): void
    {
        if (!isset($this->channelSubscribers[$channel])) {
            return;
        }

        $message = json_encode([
            'event'   => $event,
            'channel' => $channel,
            'data'    => is_string($data) ? $data : json_encode($data),
        ]);

        $frame = $this->encodeFrame($message);

        foreach (array_keys($this->channelSubscribers[$channel]) as $clientId) {
            if ($excludeClientId !== null && $clientId === $excludeClientId) {
                continue;
            }

            if (isset($this->clients[$clientId])) {
                $this->sendFrame($this->clients[$clientId], $frame);
            }
        }
    }

    /**
     * Unsubscribe a client from a channel and handle presence departures.
     */
    protected function leaveChannel(int $clientId, string $channel): void
    {
        if (isset($this->channelSubscribers[$channel][$clientId])) {
            unset($this->channelSubscribers[$channel][$clientId]);
        }

        if (isset($this->clientMeta[$clientId])) {
            unset($this->clientMeta[$clientId]['channels'][$channel]);

            if (str_starts_with($channel, 'presence-') && isset($this->clientMeta[$clientId]['presence'][$channel])) {
                $userId = $this->clientMeta[$clientId]['presence'][$channel]['user_id'];
                unset($this->clientMeta[$clientId]['presence'][$channel]);
                unset($this->presenceMembers[$channel][$userId]);

                $this->broadcastToChannel($channel, 'pusher_internal:member_removed', [
                    'user_id' => $userId,
                ], $clientId);
            }
        }
    }

    /**
     * Close a client connection cleanly.
     */
    protected function closeClient($client): void
    {
        $id = (int) $client;

        if (isset($this->clientMeta[$id])) {
            foreach (array_keys($this->clientMeta[$id]['channels']) as $channel) {
                $this->leaveChannel($id, $channel);
            }
            unset($this->clientMeta[$id]);
        }

        unset($this->clients[$id]);

        if (is_resource($client)) {
            @fclose($client);
        }
    }

    /**
     * Send raw frame to client.
     */
    protected function sendFrame($client, string $frame): void
    {
        @fwrite($client, $frame);
    }

    /**
     * Send text message encoded as RFC 6455 frame.
     */
    protected function sendText($client, string $text): void
    {
        $this->sendFrame($client, $this->encodeFrame($text));
    }

    /**
     * Encode payload into an RFC 6455 frame (server to client is unmasked).
     */
    protected function encodeFrame(string $payload, int $opcode = 0x1): string
    {
        $length = strlen($payload);
        $firstByte = 0x80 | ($opcode & 0x0f); // FIN = 1 + opcode

        if ($length <= 125) {
            $header = chr($firstByte) . chr($length);
        } elseif ($length <= 65535) {
            $header = chr($firstByte) . chr(126) . pack('n', $length);
        } else {
            $header = chr($firstByte) . chr(127) . pack('J', $length);
        }

        return $header . $payload;
    }

    /**
     * Decode an RFC 6455 client frame (client to server is masked).
     *
     * @return array{opcode: int, payload: string, bytes_consumed: int}|null
     */
    protected function decodeFrame(string $data): ?array
    {
        $len = strlen($data);
        if ($len < 2) {
            return null;
        }

        $byte1 = ord($data[0]);
        $byte2 = ord($data[1]);

        $opcode = $byte1 & 0x0f;
        $isMasked = ($byte2 & 0x80) !== 0;
        $payloadLen = $byte2 & 0x7f;

        $offset = 2;

        if ($payloadLen === 126) {
            if ($len < 4) return null;
            $payloadLen = unpack('n', substr($data, 2, 2))[1];
            $offset = 4;
        } elseif ($payloadLen === 127) {
            if ($len < 10) return null;
            $payloadLen = unpack('J', substr($data, 2, 8))[1];
            $offset = 10;
        }

        $mask = '';
        if ($isMasked) {
            if ($len < $offset + 4) return null;
            $mask = substr($data, $offset, 4);
            $offset += 4;
        }

        if ($len < $offset + $payloadLen) {
            return null; // Full payload not yet received
        }

        $payload = substr($data, $offset, $payloadLen);

        if ($isMasked) {
            $unmasked = '';
            for ($i = 0; $i < $payloadLen; $i++) {
                $unmasked .= $payload[$i] ^ $mask[$i % 4];
            }
            $payload = $unmasked;
        }

        return [
            'opcode'         => $opcode,
            'payload'        => $payload,
            'bytes_consumed' => $offset + $payloadLen,
        ];
    }

    /**
     * Register POSIX signals for graceful shutdown.
     */
    protected function registerSignalHandlers(): void
    {
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, function () {
                $this->stop();
            });
            pcntl_signal(SIGINT, function () {
                $this->stop();
            });
        }
    }
}
