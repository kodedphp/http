<?php

namespace Koded\Http;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class ServerResponseTest extends TestCase
{
    /**
     * @var bool COntrol the testing headers_sent()
     */
    public static $HEADERS_SENT = false;

    public function test_constructor_and_default_state()
    {
        $response = new ServerResponse;

        $this->assertSame(StatusCode::OK, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame('text/html', $response->getContentType());
        $this->assertSame('1.1', $response->getProtocolVersion());

        $this->assertInstanceOf(StreamInterface::class, $response->getBody());
    }

    public function test_constructor_arguments()
    {
        $response = (new ServerResponse('エンコーディングは難しくない', StatusCode::BAD_GATEWAY))
            ->withHeader('Content-type', 'application/json');

        $this->assertSame(StatusCode::BAD_GATEWAY, $response->getStatusCode());
        $this->assertSame('Bad Gateway', $response->getReasonPhrase());
        $this->assertSame('application/json', $response->getContentType());

        $response->getBody()->rewind();
        $this->assertSame('エンコーディングは難しくない', $response->getBody()->getContents());
    }

    public function test_should_set_status_code_without_phrase()
    {
        $response = new ServerResponse;
        $this->assertSame(200, $response->getStatusCode());

        $other = $response->withStatus(100);
        $this->assertNotSame($response, $other, 'The object is immutable');

        $this->assertSame(100, $other->getStatusCode());
        $this->assertSame('Continue', $other->getReasonPhrase(), 'Without explicitly setting the reason phrase');
    }

    public function test_should_set_status_code_with_reason_phrase()
    {
        $response = new ServerResponse;
        $response = $response->withStatus(204, 'Custom phrase');
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('Custom phrase', $response->getReasonPhrase(), 'Set custom reason phrase');
    }

    /**
     *
     */
    public function test_should_throw_exception_on_invalid_status_code()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMEssage('Invalid status code 999, expected range between [100-599]');

        (new ServerResponse)->withStatus(999);
    }

    public function test_send_method()
    {
        self::$HEADERS_SENT = true;

        $response = new ServerResponse('hello world');
        $output   = $response->send();

        $this->assertSame('hello world', $output);
        $this->assertSame(['11'], $response->getHeader('Content-Length'),
            'The length (int) is transformed to string by normalizeHeader()');
    }

    public function test_send_with_bodiless_status_code()
    {
        $response = new ServerResponse('hello world', StatusCode::NO_CONTENT, [
            'content-type' => 'text/html'
        ]);

        $this->assertTrue($response->hasHeader('content-type'));
        $output = $response->send();

        $this->assertSame('', $output);
        $this->assertFalse($response->hasHeader('Content-Length'));
        $this->assertFalse($response->hasHeader('Content-Type'));
        $this->assertSame(0, $response->getBody()->getSize());
        $this->assertSame(StatusCode::NO_CONTENT, $response->getStatusCode());
    }

    public function test_send_with_head_http_method()
    {
        $_SERVER['REQUEST_METHOD'] = 'HEAD';

        $response = new ServerResponse('hello world');
        $output   = $response->send();

        $this->assertSame('', $output, 'The body for HEAD request is empty');
        $this->assertSame(['11'], $response->getHeader('Content-Length'),
            'Content length for HEAD request is provided');
    }

    public function test_send_with_transfer_encoding()
    {
        $response = new ServerResponse('hello world', 200, [
            'transfer-encoding' => 'chunked'
        ]);

        $response->send();

        $this->assertArrayNotHasKey('Content-Length', $response->getHeaders());
        $this->assertAttributeNotContains('content-length', 'headers', $response);
        $this->assertFalse($response->hasHeader('content-length'));
    }

    protected function tearDown()
    {
        self::$HEADERS_SENT = false;
    }
}

/**
 * Override the native header functions for testing
 */

function header() { }

function headers_sent()
{
    return ServerResponseTest::$HEADERS_SENT;
}