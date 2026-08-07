<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Http\Exception;

use RuntimeException;

class HttpException extends RuntimeException
{
    protected int $statusCode;
    protected array $headers;

    public function __construct(int $statusCode, string $message = '', ?\Throwable $previous = null, array $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->headers    = $headers;

        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}

class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Not Found', ?\Throwable $previous = null)
    {
        parent::__construct(404, $message, $previous);
    }
}

class MethodNotAllowedException extends HttpException
{
    public function __construct(array $allowedMethods, string $path = '')
    {
        parent::__construct(
            405,
            "Method Not Allowed for [{$path}]. Allowed: " . implode(', ', $allowedMethods),
            null,
            ['Allow' => implode(', ', $allowedMethods)]
        );
    }
}

class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct(401, $message);
    }
}

class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'Forbidden')
    {
        parent::__construct(403, $message);
    }
}
