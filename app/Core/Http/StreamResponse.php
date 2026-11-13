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

namespace Papastro\Http;

use Closure;

/**
 * Pastream — Realtime Server-Sent Events (SSE) and Streaming Response Engine.
 * Supports streaming LLM tokens, live logs, data exports, and chunked transfer.
 */
class StreamResponse extends Response
{
    /**
     * The callback used to generate stream chunks.
     */
    protected Closure $callback;

    /**
     * Create a new stream response.
     */
    public function __construct(callable $callback, int $status = 200, array $headers = [])
    {
        parent::__construct('', $status, $headers);
        $this->callback = Closure::fromCallable($callback);

        if (!$this->hasHeader('Cache-Control')) {
            $this->setHeader('Cache-Control', 'no-cache, no-transform, must-revalidate');
        }

        if (!$this->hasHeader('X-Accel-Buffering')) {
            $this->setHeader('X-Accel-Buffering', 'no'); // Disable Nginx proxy buffering
        }
    }

    /**
     * Create a Server-Sent Events (SSE) stream response.
     */
    public static function sse(callable $callback, array $headers = []): static
    {
        $defaultHeaders = array_merge([
            'Content-Type'      => 'text/event-stream; charset=UTF-8',
            'Cache-Control'     => 'no-cache, no-transform',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ], $headers);

        return new static($callback, 200, $defaultHeaders);
    }

    /**
     * Send the stream content with immediate flushing.
     */
    public function sendContent(): void
    {
        // Flush any existing output buffers to ensure immediate delivery in web SAPIs
        if (ob_get_level() > 0 && PHP_SAPI !== 'cli') {
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }
        }

        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }

        @ini_set('zlib.output_compression', '0');
        @ini_set('implicit_flush', '1');
        ob_implicit_flush(true);

        $emitter = new class {
            /**
             * Emit a raw chunk to the client.
             */
            public function write(string $data): void
            {
                echo $data;
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                @flush();
            }

            /**
             * Format and emit a Server-Sent Event (SSE) message.
             */
            public function event(mixed $data, ?string $event = null, string|int|null $id = null, ?int $retry = null): void
            {
                $payload = '';

                if ($event !== null) {
                    $payload .= "event: {$event}\n";
                }

                if ($id !== null) {
                    $payload .= "id: {$id}\n";
                }

                if ($retry !== null) {
                    $payload .= "retry: {$retry}\n";
                }

                $formattedData = is_array($data) || is_object($data) ? json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : (string) $data;

                foreach (explode("\n", (string) $formattedData) as $line) {
                    $payload .= "data: {$line}\n";
                }

                $payload .= "\n";

                $this->write($payload);
            }

            /**
             * Send a ping/heartbeat comment to prevent client timeout.
             */
            public function ping(): void
            {
                $this->write(": ping\n\n");
            }
        };

        ($this->callback)($emitter);
    }

    /**
     * Check if client connection is still active.
     */
    public static function isClientActive(): bool
    {
        return !connection_aborted();
    }
}
