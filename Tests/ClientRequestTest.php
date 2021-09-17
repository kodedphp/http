<?php

namespace Tests\Koded\Http;

use InvalidArgumentException;
use Koded\Http\ClientRequest;
use Koded\Http\Interfaces\HttpStatus;
use Koded\Http\Interfaces\Request;
use Koded\Http\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

class ClientRequestTest extends TestCase
{
    private ClientRequest|Request $SUT;

    public function test_defaults()
    {
        $this->assertSame(Request::POST, $this->SUT->getMethod());
        $this->assertInstanceOf(UriInterface::class, $this->SUT->getUri());
        $this->assertSame('/', $this->SUT->getRequestTarget(), "No URI (path) and no request-target is provided");
    }

    public function test_uri()
    {
        $this->assertInstanceOf(Uri::class, $this->SUT->getUri());
    }

    public function test_should_change_the_method()
    {
        $request = $this->SUT->withMethod('get');
        $this->assertSame('GET', $request->getMethod());
    }

    public function test_request_target()
    {
        $request = $this->SUT->withRequestTarget('42');
        $this->assertSame('42', $request->getRequestTarget());
        $this->assertNotSame($request, $this->SUT);
    }

    public function test_request_target_with_query_string()
    {
        $uri     = new Uri('http://example.net/home?foo=bared');
        $request = $this->SUT->withUri($uri);

        $this->assertSame('/home?foo=bared', $request->getRequestTarget());
        $this->assertNotSame($request, $this->SUT);
    }

    public function test_invalid_request_target()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ClientRequest::E_INVALID_REQUEST_TARGET);
        $this->expectExceptionCode(HttpStatus::BAD_REQUEST);

        $this->SUT->withRequestTarget('foo bar');
    }

    public function test_with_uri_preserving_the_host()
    {
        $uri     = new Uri('http://example.net/42');
        $request = $this->SUT->withUri($uri, true);

        $this->assertSame(['example.org'], $request->getHeader('host'), 'The previous host is preserved in the header');
        $this->assertSame('example.net', $request->getUri()->getHost(), 'Request URI has its own hostname');
        $this->assertEquals('/42', $request->getPath());
    }

    public function test_with_uri_and_not_preserving_the_host()
    {
        $uri     = new Uri('http://example.net/42');
        $request = $this->SUT->withUri($uri, false);
        $this->assertSame(['example.net'], $request->getHeader('host'), 'Host is taken from Uri');
    }

    public function test_construction_with_array_body()
    {
        $this->SUT = new ClientRequest('GET', 'http://example.org', ['foo' => 'bar']);
        $this->assertSame('{"foo":"bar"}', $this->SUT->getBody()->getContents());
    }

    public function test_construction_with_iterable_body()
    {
        $this->SUT = new ClientRequest('GET', 'http://example.org', new \ArrayObject(['foo' => 'bar']));
        $this->assertSame('{"foo":"bar"}', $this->SUT->getBody()->getContents());
    }

    protected function setUp(): void
    {
        $this->SUT = new ClientRequest('POST', 'http://example.org');
    }

    protected function tearDown(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }
}
