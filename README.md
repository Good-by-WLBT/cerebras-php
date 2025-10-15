Cerebras PHP Client

Install
- composer require your-vendor/cerebras-php

Usage
- Set CEREBRAS_API_KEY in your environment.
- See examples/basic.php for creating non-streaming and streaming chat completions.

Notes
- Endpoints and payloads should match the official Cerebras AI API. Adjust model IDs and fields accordingly.
- Includes retry with backoff for 429/5xx, SSE streaming, and basic helpers.