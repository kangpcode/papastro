<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v1.2.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Http;

/**
 * Represents an outgoing HTTP response.
 */
class Response
{
    /**
     * The response content.
     */
    protected string $content;

    /**
     * The HTTP status code.
     */
    protected int $statusCode;

    /**
     * The response headers.
     */
    protected array $headers;

    /**
     * The HTTP status text map.
     */
    protected static array $statusTexts = [
        100 => 'Continue',
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    /**
     * Create a new Response instance.
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = array_merge(['Content-Type' => 'text/html; charset=UTF-8'], $headers);
    }

    /**
     * Create a new Response.
     */
    public static function make(string $content = '', int $status = 200, array $headers = []): static
    {
        return new static($content, $status, $headers);
    }

    /**
     * Create a JSON Response.
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): static
    {
        return new static(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            $status,
            array_merge($headers, ['Content-Type' => 'application/json'])
        );
    }

    /**
     * Create a redirect Response.
     */
    public static function redirect(string $url, int $status = 302): static
    {
        return new static('', $status, ['Location' => $url]);
    }

    /**
     * Create a 404 response.
     */
    public static function notFound(string $message = 'Not Found'): static
    {
        return new static($message, 404);
    }

    /**
     * Set the response content.
     */
    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get the response content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set the HTTP status code.
     */
    public function setStatusCode(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set a response header.
     */
    public function header(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Set multiple headers at once.
     */
    public function withHeaders(array $headers): static
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Set Content-Type header.
     */
    public function contentType(string $type): static
    {
        $this->headers['Content-Type'] = $type;
        return $this;
    }

    /**
     * Add a cookie to the response.
     */
    public function cookie(
        string $name,
        string $value,
        int $minutes = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true
    ): static {
        $expires = $minutes > 0 ? time() + ($minutes * 60) : 0;
        setcookie($name, $value, $expires, $path, $domain, $secure, $httpOnly);
        return $this;
    }

    /**
     * Send the response to the client.
     */
    public function send(): void
    {
        $this->sendHeaders();
        $this->sendContent();
    }

    /**
     * Send HTTP headers.
     */
    protected function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        $statusText = static::$statusTexts[$this->statusCode] ?? 'Unknown';
        header("HTTP/1.1 {$this->statusCode} {$statusText}");

        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }
    }

    /**
     * Send the response content.
     */
    protected function sendContent(): void
    {
        echo $this->content;
    }

    /**
     * Get all response headers.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Determine if the response is successful.
     */
    public function isOk(): bool
    {
        return $this->statusCode === 200;
    }

    /**
     * Determine if the response is a redirect.
     */
    public function isRedirect(): bool
    {
        return in_array($this->statusCode, [301, 302, 303, 307, 308]);
    }

    /**
     * Convert the response to a string.
     */
    public function __toString(): string
    {
        return $this->content;
    }
}
