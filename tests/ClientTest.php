<?php

declare(strict_types=1);

namespace Goed\Cerebras\Tests;

use Goed\Cerebras\Client;
use Goed\Cerebras\Config;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function testConstruct(): void
    {
        $client = new Client(new Config('test-key'));
        $this->assertInstanceOf(Client::class, $client);
    }
}