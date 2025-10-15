<?php

declare(strict_types=1);

namespace Goed\Cerebras\Tests;

use Goed\Cerebras\Client;
use Goed\Cerebras\Config;
use PHPUnit\Framework\TestCase;

final class ClientConstructionTest extends TestCase
{
    public function testConstructClient(): void
    {
        $client = new Client(new Config('test-key'));
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testEmptyApiKeyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Config('');
    }
}