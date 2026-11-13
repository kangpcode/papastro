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

namespace Papastro\Http\Client;

use RuntimeException;

/**
 * PasFetch Fluent Pending HTTP Request.
 */
class PendingRequest
{
    protected array $headers = [
        'User-Agent' => 'Papastro-PasFetch/4.0.0',
        'Accept'     => 'application/json, text/plain, */*',
    ];

    protected array $options = [];
    protected int $timeout = 30;
    protected int $retries = 0;
    protected int $retrySleepMs = 100;
    protected string $contentType = 'application/json';

    public function withHeaders(array $headers): static
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function withToken(string $token, string $type = 'Bearer'): static
    {
        $this->headers['Authorization'] = "{$type} {$token}";
        return $this;
    }

    public function withBasicAuth(string $username, string $password): static
    {
        $this->headers['Authorization'] = 'Basic ' . base64_encode("{$username}:{$password}");
        return $this;
    }

    public function asJson(): static
    {
        $this->contentType = 'application/json';
        $this->headers['Content-Type'] = 'application/json';
        return $this;
    }

    public function asForm(): static
    {
        $this->contentType = 'application/x-www-form-urlencoded';
        $this->headers['Content-Type'] = 'application/x-www-form-urlencoded';
        return $this;
    }

    public function timeout(int $seconds): static
    {
        $this->timeout = $seconds;
        return $this;
    }

    public function retry(int $times, int $sleepMs = 100): static
    {
        $this->retries = $times;
        $this->retrySleepMs = $sleepMs;
        return $this;
    }

    public function get(string $url, array $query = []): Response
    {
        if (!empty($query)) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . http_build_query($query);
        }
        return $this->send('GET', $url);
    }

    public function post(string $url, array|string $data = []): Response
    {
        return $this->send('POST', $url, $data);
    }

    public function put(string $url, array|string $data = []): Response
    {
        return $this->send('PUT', $url, $data);
    }

    public function patch(string $url, array|string $data = []): Response
    {
        return $this->send('PATCH', $url, $data);
    }

    public function delete(string $url, array|string $data = []): Response
    {
        return $this->send('DELETE', $url, $data);
    }

    public function send(string $method, string $url, array|string $data = []): Response
    {
        $attempts = 0;
        $maxAttempts = 1 + $this->retries;

        while ($attempts < $maxAttempts) {
            $attempts++;
            try {
                return $this->execute($method, $url, $data);
            } catch (\Throwable $e) {
                if ($attempts >= $maxAttempts) {
                    throw new RuntimeException("HTTP request to [{$url}] failed: " . $e->getMessage(), 0, $e);
                }
                usleep($this->retrySleepMs * 1000);
            }
        }

        throw new RuntimeException("HTTP request to [{$url}] failed after {$maxAttempts} attempts.");
    }

    protected function execute(string $method, string $url, array|string $data = []): Response
    {
        $method = strtoupper($method);

        if (function_exists('curl_init')) {
            return $this->executeWithCurl($method, $url, $data);
        }

        return $this->executeWithStreams($method, $url, $data);
    }

    protected function executeWithCurl(string $method, string $url, array|string $data): Response
    {
        $ch = curl_init();

        $headers = [];
        foreach ($this->headers as $k => $v) {
            $headers[] = "{$k}: {$v}";
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

        if (!empty($data)) {
            $payload = is_array($data) && $this->contentType === 'application/json'
                ? json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : (is_array($data) ? http_build_query($data) : (string) $data);

            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $rawResponse = curl_exec($ch);

        if ($rawResponse === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException($error);
        }

        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $rawHeaders = substr((string) $rawResponse, 0, $headerSize);
        $body = substr((string) $rawResponse, $headerSize);

        return new Response($statusCode, (string) $body, $this->parseHeaders($rawHeaders));
    }

    protected function executeWithStreams(string $method, string $url, array|string $data): Response
    {
        $headerLines = [];
        foreach ($this->headers as $k => $v) {
            $headerLines[] = "{$k}: {$v}";
        }

        $content = '';
        if (!empty($data)) {
            $content = is_array($data) && $this->contentType === 'application/json'
                ? json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : (is_array($data) ? http_build_query($data) : (string) $data);
        }

        $context = stream_context_create([
            'http' => [
                'method'        => $method,
                'header'        => implode("\r\n", $headerLines),
                'content'       => $content,
                'timeout'       => $this->timeout,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        $status = 200;
        $headers = [];

        if (isset($http_response_header) && is_array($http_response_header)) {
            $headers = $this->parseHeaders(implode("\r\n", $http_response_header));
            if (isset($http_response_header[0]) && preg_match('#HTTP/\S+\s+(\d+)#', $http_response_header[0], $m)) {
                $status = (int) $m[1];
            }
        }

        return new Response($status, (string) $body, $headers);
    }

    protected function parseHeaders(string $rawHeaders): array
    {
        $headers = [];
        $lines = explode("\r\n", trim($rawHeaders));

        foreach ($lines as $line) {
            if (str_contains($line, ':')) {
                [$k, $v] = explode(':', $line, 2);
                $headers[trim($k)] = trim($v);
            }
        }

        return $headers;
    }
}

