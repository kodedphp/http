<?php

namespace Koded\Http;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class ServerResponseTest extends TestCase
{

    public function test_constructor_and_default_state()
    {
        $response = new ServerResponse;

        $this->assertSame(HttpStatus::OK, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame('text/html', $response->getContentType());
        $this->assertSame('UTF-8', $response->getCharset());
        $this->assertSame('1.1', $response->getProtocolVersion());

        $this->assertInstanceOf(StreamInterface::class, $response->getBody());
        $this->assertSame(['Content-Type' => 'text/html'], $response->getHeaders());
    }

    public function test_constructor_arguments()
    {
        $response = new ServerResponse('lorem ipsum', HttpStatus::BAD_GATEWAY, 'json', 'utf-16');
        $this->assertSame(HttpStatus::BAD_GATEWAY, $response->getStatusCode());
        $this->assertSame('Bad Gateway', $response->getReasonPhrase());
        $this->assertSame('application/json', $response->getContentType());
        $this->assertSame('utf-16', $response->getCharset());
        $this->assertSame('lorem ipsum', $response->getBody()->getContents());
    }

    public function test_should_set_status_code_without_phrase()
    {
        $response = new ServerResponse;
        $response = $response->withStatus(100);
        $this->assertSame(100, $response->getStatusCode());
        $this->assertSame('Continue', $response->getReasonPhrase(), 'Without setting the reason phrase');
    }

    public function test_should_set_status_code_with_reason_phrase()
    {
        $response = new ServerResponse;
        $response = $response->withStatus(204, 'Custom phrase');
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('Custom phrase', $response->getReasonPhrase(), 'Set the reason phrase with custom crap');
    }

    /**
     *
     */
    public function test_should_throw_exception_on_invalid_status_code()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMEssage('Invalid status code, expected range between [100-599]');

        (new ServerResponse)->withStatus(999);

    }
}