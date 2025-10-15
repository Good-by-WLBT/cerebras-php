<?php

declare(strict_types=1);

namespace Goed\Cerebras\Tests;

use Goed\Cerebras\Http\ResponseDecoder;
use PHPUnit\Framework\TestCase;

final class ResponseDecoderTest extends TestCase
{
    public function testDecodeValidJson(): void
    {
        $data = ResponseDecoder::decodeJson('{"a":1,"b":"x"}');
        $this->assertSame(['a' => 1, 'b' => 'x'], $data);
    }

    public function testDecodeInvalidJsonThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        ResponseDecoder::decodeJson('{invalid}');
    }
}