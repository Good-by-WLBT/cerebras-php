# Cerebras PHP Client

A PHP client library for interacting with the Cerebras AI API, providing easy access to chat completions and other endpoints.

## Installation

Install the package via Composer:

```bash
composer require your-vendor/cerebras-php
```

## Usage

### Setup

Set your API key as an environment variable:

```bash
export CEREBRAS_API_KEY=your_api_key_here
```

### Basic Chat Completion

Create a non-streaming chat completion:

```php
<?php
require 'vendor/autoload.php';

use Goed\Cerebras\Client;

$client = new Client();
$response = $client->chat()->create([
    'model' => 'llama3.1-8b',
    'messages' => [
        ['role' => 'user', 'content' => 'Hello, how are you?']
    ]
]);

echo $response['choices'][0]['message']['content'];
```

### Streaming Chat Completion

For real-time responses, use streaming:

```php
<?php
require 'vendor/autoload.php';

use Goed\Cerebras\Client;

$client = new Client();
$stream = $client->chat()->create([
    'model' => 'llama3.1-8b',
    'messages' => [
        ['role' => 'user', 'content' => 'Tell me a story.']
    ],
    'stream' => true
]);

foreach ($stream as $chunk) {
    echo $chunk['choices'][0]['delta']['content'] ?? '';
}
```

For more examples, see the `examples/` directory, including `basic.php`.

## Features

- **API Compatibility**: Endpoints and payloads match the official Cerebras AI API. Adjust model IDs and fields as needed.
- **Retry Mechanism**: Automatic retry with backoff for 429 (rate limit) and 5xx errors.
- **Streaming Support**: Server-Sent Events (SSE) for real-time chat completions.
- **Helpers**: Basic utility functions for common tasks.

## Notes

Ensure your API key is valid and has the necessary permissions. Refer to the [Cerebras AI documentation](https://docs.cerebras.ai) for detailed API specifications.