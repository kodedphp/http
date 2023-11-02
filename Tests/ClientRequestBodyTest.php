<?php

namespace Tests\Koded\Http;

use Koded\Http\ClientRequest;
use Koded\Http\Interfaces\HttpMethod;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class ClientRequestBodyTest extends TestCase
{
    const URI = 'https://example.org';

    public function test_with_string_body()
    {
        $request = new ClientRequest(HttpMethod::POST, self::URI, 'TDD');
        $this->assertInstanceOf(StreamInterface::class, $request->getBody());
        $this->assertSame('TDD', (string)$request->getBody());
    }

    public function test_without_body_attribute()
    {
        $request = new ClientRequest(HttpMethod::GET, self::URI);
        $this->assertInstanceOf(StreamInterface::class, $request->getBody());
        $this->assertSame('', (string)$request->getBody());
    }
}
