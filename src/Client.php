<?php

declare(strict_types=1);

namespace Goed\Cerebras;

use Goed\Cerebras\Exceptions\ApiException;
use Goed\Cerebras\Http\ResponseDecoder;
use Goed\Cerebras\Http\RetryMiddlewareFactory;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Psr\Log\LoggerInterface;

final class Client
{
    private GuzzleClient $http;
    private Config $config;
    private ?LoggerInterface $logger;

    public function __construct(Config $config, ?LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger;

        $stack = HandlerStack::create();
        $stack->push(RetryMiddlewareFactory::create($logger));

        $this->http = new GuzzleClient([
            'base_uri' => rtrim($config->baseUrl, '/') . '/',
            'timeout' => $config->timeoutSeconds,
            'connect_timeout' => $config->connectTimeoutSeconds,
            'handler' => $stack,
            'headers' => array_filter([
                'Authorization' => 'Bearer ' . $config->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Organization' => $config->organization,
            ]),
        ]);
    }

    // Example: text generation / chat completions (non-streaming)
    public function createCompletion(array $payload): array
    {
        return $this->postJson('v1/chat/completions', $payload);
    }

    // Example: text generation / chat completions with streaming (Server-Sent Events)
    // This yields decoded event payloads as arrays. Caller can iterate generator.
    public function streamCompletion(array $payload): \Generator
    {
        $payload = $payload;
        $payload['stream'] = true;

        $uri = 'v1/chat/completions';

        try {
            $response = $this->http->request('POST', $uri, [
                'json' => $payload,
                'stream' => true,
                'headers' => [
                    'Accept' => 'text/event-stream',
                ],
            ]);

            $body = $response->getBody();

            $buffer = '';
            while (!$body->eof()) {
                $chunk = $body->read(8192);
                if ($chunk === '') {
                    continue;
                }
                $buffer .= $chunk;

                while (($pos = strpos($buffer, "\n\n")) !== false) {
                    $event = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 2);

                    foreach (explode("\n", $event) as $line) {
                        $line = trim($line);
                        if ($line === '' || str_starts_with($line, ':')) {
                            continue;
                        }
                        if (str_starts_with($line, 'data:')) {
                            $data = trim(substr($line, 5));
                            if ($data === '[DONE]') {
                                return;
                            }
                            if ($data !== '') {
                                yield ResponseDecoder::decodeJson($data);
                            }
                        }
                    }
                }
            }
        } catch (GuzzleException $e) {
            throw new ApiException('Streaming request failed: ' . $e->getMessage(), null, null, $e);
        }
    }

    // Model listing (if exposed by Cerebras API)
    public function listModels(): array
    {
        return $this->getJson('v1/models');
    }

    private function getJson(string $uri, array $query = []): array
    {
        try {
            $resp = $this->http->request('GET', $uri, [
                'query' => $query,
            ]);
        } catch (GuzzleException $e) {
            throw new ApiException('HTTP GET failed: ' . $e->getMessage(), null, null, $e);
        }

        $status = $resp->getStatusCode();
        $body = (string) $resp->getBody();

        if ($status < 200 || $status >= 300) {
            $decoded = $this->tryDecode($body);
            throw new ApiException('Unexpected status code', $status, $decoded);
        }

        return ResponseDecoder::decodeJson($body);
    }

    private function postJson(string $uri, array $json): array
    {
        try {
            $resp = $this->http->request('POST', $uri, [
                'json' => $json,
            ]);
        } catch (GuzzleException $e) {
            throw new ApiException('HTTP POST failed: ' . $e->getMessage(), null, null, $e);
        }

        $status = $resp->getStatusCode();
        $body = (string) $resp->getBody();

        if ($status < 200 || $status >= 300) {
            $decoded = $this->tryDecode($body);
            throw new ApiException('Unexpected status code', $status, $decoded);
        }

        return ResponseDecoder::decodeJson($body);
    }

    private function tryDecode(string $body): ?array
    {
        try {
            return ResponseDecoder::decodeJson($body);
        } catch (\Throwable) {
            return null;
        }
    }
}