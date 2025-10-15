<?php

declare(strict_types=1);

namespace Goed\Cerebras;

final class Config
{
    public function __construct(
        public readonly string $apiKey,
        public readonly string $baseUrl = 'https://api.cerebras.ai',
        public readonly ?string $organization = null,
        public readonly int $timeoutSeconds = 60,
        public readonly int $connectTimeoutSeconds = 10
    ) {
        if ($this->apiKey === '') {
            throw new \InvalidArgumentException('API key must not be empty.');
        }
    }
}