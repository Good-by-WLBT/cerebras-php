<?php

declare(strict_types=1);

namespace Goed\Cerebras\Tests;

use Goed\Cerebras\Client;
use Goed\Cerebras\Config;
use PHPUnit\Framework\TestCase;

final class IntegrationTest extends TestCase
{
    private ?Client $client = null;

    protected function setUp(): void
    {
        $apiKey = getenv('CEREBRAS_API_KEY');
        if (!$apiKey) {
            $this->markTestSkipped('CEREBRAS_API_KEY not set in environment.');
        }
        $this->client = new Client(new Config($apiKey));
    }

    public function testListModels(): void
    {
        $result = $this->client->listModels();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result); // Assuming standard OpenAI-like structure
    }

    public function testCreateCompletion(): void
    {
        $payload = [
            'model' => 'llama3.1-8b',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello, world!']
            ],
            'max_tokens' => 10,
        ];
        $result = $this->client->createCompletion($payload);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('choices', $result);
    }

    public function testStreamCompletion(): void
    {
        $payload = [
            'model' => 'llama3.1-8b',
            'messages' => [
                ['role' => 'user', 'content' => 'Say hi.']
            ],
            'max_tokens' => 5,
        ];
        $events = iterator_to_array($this->client->streamCompletion($payload));
        $this->assertNotEmpty($events);
        foreach ($events as $event) {
            $this->assertIsArray($event);
        }
    }
}