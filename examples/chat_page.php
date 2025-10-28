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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] === 'application/json') {
    $input = json_decode(file_get_contents('php://input'), true);
    $messages = $input['messages'] ?? [];

    header('Content-Type: text/plain');
    header('Cache-Control: no-cache');
    ob_end_flush();

    try {
        foreach ($client->streamCompletion([
            'model' => 'llama3.1-8b',
            'messages' => $messages,
            'temperature' => 0.3,
            'max_tokens' => 2000,
        ]) as $event) {
            $content = $event['choices'][0]['delta']['content'] ?? '';
            if ($content !== '') {
                echo $content;
                flush();
            }
        }
    } catch (\Throwable $e) {
        echo 'Error: ' . $e->getMessage();
    }
    exit;
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Cerebras Chat Demo</title>
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
        form button:disabled { background: #9ca3af; cursor: not-allowed; }
        .actions { display: flex; gap: 0.75rem; margin-top: 0.75rem; }
        .actions button.secondary { background: #6b7280; }
        .actions button.secondary:hover { background: #4b5563; }
        pre { white-space: pre-wrap; word-break: break-word; margin: 0; }
    </style>
</head>
<body>
<div class="wrapper">
    <h1>Cerebras Chat Demo</h1>

    <?php if ($apiKey === 'YOUR_API_KEY') : ?>
        <div class="notice warning">
            Set the <code>CEREBRAS_API_KEY</code> environment variable before using this page.
        </div>
    <?php endif; ?>

    <div class="chat-log" aria-live="polite" id="chat-log">
        <!-- Messages will be loaded here -->
    </div>

    <form id="chat-form">
        <label for="message">Send a message</label>
        <textarea id="message" name="message" placeholder="Ask something..." required></textarea>
        <div class="actions">
            <button type="submit" id="send-btn">Send</button>
            <button class="secondary" type="button" id="reset-btn">Reset chat</button>
        </div>
    </form>
</div>

<script>
const chatLog = document.getElementById('chat-log');
const form = document.getElementById('chat-form');
const messageInput = document.getElementById('message');
const sendBtn = document.getElementById('send-btn');
const resetBtn = document.getElementById('reset-btn');

let conversation = JSON.parse(localStorage.getItem('cerebras_chat') || '[]');

// Load existing conversation
if (chatLog) {
    conversation.forEach(msg => addMessage(msg.role, msg.content));
}

function addMessage(role, content) {
    if (!chatLog) return null;
    const div = document.createElement('div');
    div.className = `message ${role}`;
    div.innerHTML = `<strong>${role.charAt(0).toUpperCase() + role.slice(1)}</strong><pre>${content}</pre>`;
    chatLog.appendChild(div);
    chatLog.scrollTop = chatLog.scrollHeight;
    return div;
}

function saveConversation() {
    localStorage.setItem('cerebras_chat', JSON.stringify(conversation));
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const message = messageInput.value.trim();
    if (!message) return;

    sendBtn.disabled = true;
    messageInput.disabled = true;

    // Add user message
    conversation.push({role: 'user', content: message});
    addMessage('user', message);
    saveConversation();
    messageInput.value = '';

    // Prepare messages for API
    const messages = [{role: 'system', content: 'You are a helpful assistant.'}, ...conversation];

    try {
        const response = await fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({messages})
        });

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let assistantMessage = '';
        const assistantDiv = addMessage('assistant', '');
        if (!assistantDiv) {
            throw new Error('Failed to create assistant message div');
        }
        const pre = assistantDiv.querySelector('pre');

        while (true) {
            const {done, value} = await reader.read();
            if (done) break;
            const chunk = decoder.decode(value, {stream: true});
            assistantMessage += chunk;
            pre.textContent = assistantMessage;
        }

        // Add to conversation
        conversation.push({role: 'assistant', content: assistantMessage});
        saveConversation();
    } catch (error) {
        addMessage('assistant', 'Error: ' + error.message);
    } finally {
        sendBtn.disabled = false;
        messageInput.disabled = false;
        messageInput.focus();
    }
});

resetBtn.addEventListener('click', () => {
    conversation = [];
    saveConversation();
    chatLog.innerHTML = '';
});
</script>
</body>
</html>
