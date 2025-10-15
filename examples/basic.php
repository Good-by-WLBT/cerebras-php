<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Goed\Cerebras\Client;
use Goed\Cerebras\Config;

$apiKey = getenv('CEREBRAS_API_KEY') ?: 'YOUR_API_KEY';

$client = new Client(new Config(
    apiKey: $apiKey,
    baseUrl: 'https://api.cerebras.ai'
));

// Non-streaming completion
$result = $client->createCompletion([
    // Adjust to match Cerebras API schema for chat/completions
    'model' => 'llama3.1-8b',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => 'Write a haiku about the ocean.']
    ],
    'temperature' => 0.7,
    'max_tokens' => 200
]);

print_r($result);

// Streaming completion
foreach ($client->streamCompletion([
    'model' => 'llama3.1-8b',
    'messages' => [
        ['role' => 'user', 'content' => 'Explain quantum entanglement simply.']
    ],
    'temperature' => 0.3
]) as $event) {
    // Each $event is a decoded JSON SSE message chunk
    if (isset($event['choices'][0]['delta']['content'])) {
        echo $event['choices'][0]['delta']['content'];
    }
}
echo PHP_EOL;