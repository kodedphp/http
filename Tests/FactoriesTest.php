<?php

namespace Koded\Http;

use Koded\Http\Interfaces\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;


class FactoriesTest extends TestCase
{

    public function test_request_factory()
    {
        $request = (new RequestFactory)->createRequest(Request::HEAD, '/');
        $this->assertInstanceOf(RequestInterface::class, $request);
    }

    public function test_server_request_factory()
    {
        $request = (new ServerRequestFactory)->createServerRequest(
            Request::HEAD, '/', ['X_Request_Id' => '123']
        );

        $this->assertSame('/', $request->getUri()->getPath());
        $this->assertArrayHasKey('X_Request_Id', $request->getServerParams());
        $this->assertSame('123', $request->getServerParams()['X_Request_Id']);
    }

    public function test_response_factory()
    {
        $reason   = 'My custom reason phrase';
        $response = (new ResponseFactory)->createResponse(201, $reason);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame($reason, $response->getReasonPhrase());
    }

    public function test_create_stream()
    {
        $stream = (new StreamFactory)->createStream('hello');
        $this->assertSame('hello', $stream->getContents());
        $this->assertTrue($stream->isSeekable());
        $this->assertTrue($stream->isWritable());
        $this->assertTrue($stream->isReadable());
        $stream->close();
    }

    public function test_create_stream_from_file()
    {
        $stream = (new StreamFactory)->createStreamFromFile(__DIR__ . '/../LICENSE');
        $this->assertInstanceOf(FileStream::class, $stream);
        $this->assertGreaterThan(0, $stream->getSize());
        $this->assertTrue($stream->isSeekable());
        $this->assertFalse($stream->isWritable());
        $this->assertTrue($stream->isReadable());
        $stream->close();
    }

    public function test_create_stream_from_resource()
    {
        $resource = fopen('php://memory', 'r');
        $stream   = (new StreamFactory)->createStreamFromResource($resource);

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertTrue($stream->isSeekable());
        $this->assertFalse($stream->isWritable());
        $this->assertTrue($stream->isReadable());

        $stream->close();
        $resource = null;
    }
}
