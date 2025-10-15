<?php

declare(strict_types=1);

namespace Goed\Cerebras\Http;

use GuzzleHttp\Middleware;
use Psr\Log\LoggerInterface;

final class RetryMiddlewareFactory
{
    public static function create(?LoggerInterface $logger = null, int $maxRetries = 3): callable
    {
        return Middleware::retry(
            function (
                $retries,
                $request,
                $response = null,
                $exception = null
            ) use ($logger, $maxRetries) {
                if ($retries >= $maxRetries) {
                    return false;
                }

                if ($exception !== null) {
                    if ($logger) {
                        $logger->warning('Retrying after network error', [
                            'retries' => $retries,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                    return true;
                }

                if ($response) {
                    $status = $response->getStatusCode();
                    // Retry on 429 and 5xx
                    if ($status === 429 || ($status >= 500 && $status < 600)) {
                        if ($logger) {
                            $logger->warning('Retrying after HTTP error', [
                                'retries' => $retries,
                                'status' => $status,
                            ]);
                        }
                        return true;
                    }
                }

                return false;
            },
            function ($retries) {
                // Exponential backoff with jitter
                $base = 100; // ms
                $delay = $base * (2 ** $retries);
                $jitter = random_int(0, 100);
                return $delay + $jitter;
            }
        );
    }
}