<?php

declare(strict_types=1);

namespace Goed\Cerebras\Exceptions;

use Throwable;

class ApiException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly ?array $responseBody = null,
        Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}