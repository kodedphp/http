<?php

namespace Tests\Koded\Http;

use Koded\Http\Interfaces\HttpStatus;
use Koded\Http\JsonResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class JsonResponseTest extends TestCase
{
    public function test_constructor_and_default_state()
    {
        $response = new JsonResponse(null);
        $this->assertSame(HttpStatus::OK, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame('application/json', $response->getContentType());
        $this->assertSame('1.1', $response->getProtocolVersion());
        $this->assertInstanceOf(StreamInterface::class, $response->getBody());
    }

    /** @dataProvider normalContent */
    public function test_array_content($data, $expected, $size)
    {
        $response = new JsonResponse($data);
        $this->assertEquals($size, $response->getBody()->getSize());
        $this->assertSame($expected, (string)$response->getBody());
    }

    /** @dataProvider normalContent */
    public function test_iterable_content($data, $expected, $size)
    {
        $response = new JsonResponse(new \ArrayObject($data));
        $this->assertEquals($size, $response->getBody()->getSize());
        $this->assertSame($expected, (string)$response->getBody());
    }

    /** @dataProvider safeContent */
    public function test_array_safe_content($data, $expected, $size)
    {
        $response = (new JsonResponse($data))->safe();
        $this->assertEquals($size, $response->getBody()->getSize());
        $this->assertSame($expected, (string)$response->getBody());
    }

    /** @dataProvider safeContent */
    public function test_iterable_safe_content($data, $expected, $size)
    {
        $response = (new JsonResponse(new \ArrayObject($data)))->safe();
        $this->assertEquals($size, $response->getBody()->getSize());
        $this->assertSame($expected, (string)$response->getBody());
    }

    public function normalContent()
    {
        return [
            [['foo' => 'bar & "and <baz>"'], '{"foo":"bar & \"and <baz>\""}', 29],
            [['foo & bar', '<baz>'], '["foo & bar","<baz>"]', 21],
        ];
    }

    public function safeContent()
    {
        return [
            [['foo' => 'bar & "and <baz>"'], '{"foo":"bar \u0026 \u0022and \u003Cbaz\u003E\u0022"}', 52],
            [['foo & bar', '<baz>'], '["foo \u0026 bar","\u003Cbaz\u003E"]', 36],
        ];
    }
}
