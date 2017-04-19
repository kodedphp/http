<?php

namespace Koded\Http;

use InvalidArgumentException;
use Koded\Http\Interfaces\Request;
use Koded\Stdlib\Arguments;
use function Koded\Stdlib\dump;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

class ServerRequestTest extends TestCase
{

    /** @var ServerRequest */
    private $SUT;

    public function test_defaults()
    {
        $this->assertSame(Request::POST, $this->SUT->getMethod());
        //$this->assertSame('http://example.org', $this->SUT->baseuri());
        $this->assertAttributeSame('', 'server', $this->SUT);
        //$this->assertFalse($this->SUT->isXHR());
        $this->assertSame('1.1', $this->SUT->getProtocolVersion());
        //$this->assertSame('/', $this->SUT->path());
        //$this->assertSame('/', $this->SUT->basepath());

        $this->assertSame([], $this->SUT->getAttributes());
        $this->assertSame([], $this->SUT->getQueryParams());
        $this->assertSame([], $this->SUT->getCookieParams());
//        $this->assertSame([], $this->SUT->getUploadedFiles());
        $this->assertNull($this->SUT->getParsedBody());
        $this->assertTrue(count($this->SUT->getHeaders()) > 0);
        $this->assertSame($_SERVER, $this->SUT->getServerParams());
    }

    public function test_should_handle_arguments()
    {
        $this->assertNull($this->SUT->getAttribute('foo'));

        $request = $this->SUT->withAttribute('foo', 'bar');
        $this->assertSame('bar', $request->getAttribute('foo'));
        $this->assertNotSame($request, $this->SUT);

        $request = $request->withoutAttribute('foo');
        $this->assertNull($request->getAttribute('foo'));
    }

    public function test_query_array()
    {
        $request = $this->SUT->withQueryParams(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $request->getQueryParams());
        $this->assertNotSame($request, $this->SUT);

        $request = $request->withQueryParams(['a' => 123]);
        $this->assertSame(['foo' => 'bar', 'a' => 123], $request->getQueryParams());
    }

    public function test_parsed_body_with_null_value()
    {
        $request = $this->SUT->withParsedBody(null);
        $this->assertNull($request->getParsedBody(), 'Indicates absence of body content');
    }

    public function test_parsed_body_with_array_data()
    {
        $request = $this->SUT->withParsedBody(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $request->getParsedBody());
    }

    public function test_parsed_body_with_iterable_value()
    {
        $request = $this->SUT->withParsedBody(new Arguments(['foo' => 'bar']));
        $this->assertSame(['foo' => 'bar'], $request->getParsedBody());
    }

    public function test_parsed_body_throws_exception_on_unsupported_values()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported data provided, Expects NULL, array or object');
        $this->SUT->withParsedBody('junk');
    }

    public function test_extra_methods()
    {
        $this->assertFalse($this->SUT->isXHR());
        $this->assertFalse($this->SUT->isMethodSafe());
        $this->assertFalse($this->SUT->isSecure());
    }

    public function test_should_create_uri_instance_without_server_name_or_address()
    {
        unset($_SERVER['SERVER_NAME'], $_SERVER['SERVER_ADDR']);
        $request = new ServerRequest;
        $this->assertInstanceOf(UriInterface::class, $request->getUri());
    }

    public function test_should_set_host_header_from_uri_instance()
    {
        unset($_SERVER['SERVER_NAME'], $_SERVER['SERVER_ADDR'], $_SERVER['HTTP_HOST']);

        $request = new ServerRequest;
        $this->assertSame([], $request->getHeader('host'));

        $request = $request->withUri(new Uri('http://example.org/'));
        $this->assertSame(['example.org'], $request->getHeader('host'));
    }

    public function test_should_replace_object_attributes()
    {
        $request = new ServerRequest(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $request->getAttributes());

        $new = $request->withAttributes(['qux' => 'zim']);
        $this->assertSame(['qux' => 'zim'], $new->getAttributes());
    }

    public function test_should_replace_cookies()
    {
        $_COOKIE = [
            'testcookie' => 'value',
            'logged'     => '0'
        ];

        $request = new ServerRequest;
        $this->assertSame($_COOKIE, $request->getCookieParams());

        $request = $request->withCookieParams(['logged' => '1']);
        $this->assertSame(['logged' => '1'], $request->getCookieParams());
    }

    protected function setUp()
    {
        $_SERVER['REQUEST_METHOD']  = 'POST';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['SERVER_NAME']     = 'example.org';
        $_SERVER['SERVER_PORT']     = 8080;
        $_SERVER['REQUEST_URI']     = '/';
        $_SERVER['SCRIPT_FILENAME'] = '/index.php';

        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_HOST']         = 'example.org';

        $_SERVER['HTTP_IF_NONE_MATCH'] = '0163b37c-08e0-46f8-9aec-f31991bf6078-gzip';

        $this->SUT = new ServerRequest;
    }
}
