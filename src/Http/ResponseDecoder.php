<?php

declare(strict_types=1);

namespace Goed\Cerebras\Http;

final class ResponseDecoder
{
    public static function decodeJson(string $body): array
    {
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to decode JSON: ' . json_last_error_msg());
        }
        return $data;
    }
}