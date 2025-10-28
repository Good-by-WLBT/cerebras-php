<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../vendor/autoload.php';

use Goed\Cerebras\Client;
use Goed\Cerebras\Config;

$apiKey = getenv('CEREBRAS_API_KEY') ?: 'YOUR_API_KEY';

$client = new Client(new Config(
    apiKey: $apiKey,
    baseUrl: 'https://api.cerebras.ai'
));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    $_SESSION['chat_conversation'] = [
        ['role' => 'system', 'content' => 'You are a helpful assistant.']
    ];
    header('Location: ' . basename(__FILE__));
    exit;
}

$conversation = $_SESSION['chat_conversation'] ?? [
    ['role' => 'system', 'content' => 'You are a helpful assistant.']
];
$error = null;

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['message'])
    && trim((string) $_POST['message']) !== ''
) {
    $userMessage = trim((string) $_POST['message']);
    $conversation[] = ['role' => 'user', 'content' => $userMessage];

    try {
        $response = $client->createCompletion([
            'model' => 'llama3.1-8b',
            'messages' => $conversation,
            'temperature' => 0.3,
            'max_tokens' => 2000,
        ]);

        $assistantReply = $response['choices'][0]['message']['content'] ?? '';
        if ($assistantReply !== '') {
            $conversation[] = ['role' => 'assistant', 'content' => $assistantReply];
        } else {
            $error = 'The API response did not include assistant content.';
        }
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }

    $_SESSION['chat_conversation'] = $conversation;
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Cerebras Chat Demo (Non-Streaming)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; background: #f4f6f8; }
        .wrapper { max-width: 720px; margin: 0 auto; padding: 1.5rem; background: #fff; border-radius: 8px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1); }
        h1 { margin-top: 0; }
        .notice { padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .notice.warning { background: #fff4e5; border: 1px solid #f0a202; color: #6b4500; }
        .notice.error { background: #fdecea; border: 1px solid #f5c6cb; color: #8b1c1c; }
        .chat-log { border: 1px solid #d6d9dc; border-radius: 6px; padding: 1rem; background: #fafbfc; max-height: 420px; overflow-y: auto; }
        .message { margin-bottom: 1rem; }
        .message:last-child { margin-bottom: 0; }
        .message strong { display: block; margin-bottom: 0.25rem; }
        .message.user { background: #e6f4ff; padding: 0.75rem; border-radius: 6px; }
        .message.assistant { background: #f1f8f5; padding: 0.75rem; border-radius: 6px; }
        form textarea { width: 100%; min-height: 120px; padding: 0.75rem; border-radius: 6px; border: 1px solid #d6d9dc; resize: vertical; }
        form button { background: #2563eb; color: #fff; font-size: 1rem; padding: 0.65rem 1.4rem; border: none; border-radius: 6px; cursor: pointer; }
        form button:hover { background: #1d4ed8; }
        .actions { display: flex; gap: 0.75rem; margin-top: 0.75rem; }
        .actions button.secondary { background: #6b7280; }
        .actions button.secondary:hover { background: #4b5563; }
        pre { white-space: pre-wrap; word-break: break-word; margin: 0; }
    </style>
</head>
<body>
<div class="wrapper">
    <h1>Cerebras Chat Demo (Non-Streaming)</h1>

    <?php if ($apiKey === 'YOUR_API_KEY') : ?>
        <div class="notice warning">
            Set the <code>CEREBRAS_API_KEY</code> environment variable before using this page.
        </div>
    <?php endif; ?>

    <?php if ($error !== null) : ?>
        <div class="notice error">Error: <?php echo escape($error); ?></div>
    <?php endif; ?>

    <div class="chat-log">
        <?php foreach ($conversation as $entry) : ?>
            <?php if ($entry['role'] === 'system') { continue; } ?>
            <div class="message <?php echo escape($entry['role']); ?>">
                <strong><?php echo ucfirst(escape($entry['role'])); ?></strong>
                <pre><?php echo escape($entry['content']); ?></pre>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="post">
        <label for="message">Send a message</label>
        <textarea id="message" name="message" placeholder="Ask something..." required></textarea>
        <div class="actions">
            <button type="submit">Send</button>
            <button class="secondary" type="submit" name="reset" value="1">Reset chat</button>
        </div>
    </form>
</div>
</body>
</html>