<?php

namespace Tests\Koded\Http;

use Koded\Http\Interfaces\HttpMethod;
use Koded\Http\Interfaces\Request;
use Koded\Http\ServerRequest;
use Koded\Http\Uri;
use Koded\Stdlib\Arguments;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

class ServerRequestTest extends TestCase
{
    use AssertionTestSupportTrait;

    private ServerRequest $SUT;

    public function test_defaults()
    {
//        $this->assertSame(Request::POST, $this->SUT->getMethod());
        $this->assertSame(HttpMethod::POST->value, $this->SUT->getMethod());

        $serverSoftwareValue = $this->getObjectProperty($this->SUT, 'serverSoftware');
        $this->assertSame('', $serverSoftwareValue);

        // makes a difference
        $this->assertSame('/', $this->SUT->getPath(), 'Much useful and predictable for real life apps');
        $this->assertSame('', $this->SUT->getUri()->getPath(), 'Weird PSR-7 rule satisfied');

        $this->assertSame('http://example.org:8080', $this->SUT->getBaseUri());
        $this->assertFalse($this->SUT->isXHR());
        $this->assertSame('1.1', $this->SUT->getProtocolVersion());

        $this->assertSame([], $this->SUT->getAttributes());
        $this->assertSame([], $this->SUT->getQueryParams());
        $this->assertSame(['test' => 'fubar'], $this->SUT->getCookieParams());
        $this->assertSame([], $this->SUT->getUploadedFiles());
        $this->assertNull($this->SUT->getParsedBody());
        $this->assertTrue(count($this->SUT->getHeaders()) > 0);
        $this->assertSame($_SERVER, $this->SUT->getServerParams());

        $this->assertSame('', $this->SUT->getHeaderLine('Content-type'));
        $this->assertFalse($this->SUT->hasHeader('content-type'),
            'Content-type can be explicitly set in the request headers');
    }

    public function test_server_uri_value()
    {
        $_SERVER['REQUEST_URI'] = 'https://example.org';

        $request = new ServerRequest;
        $this->assertSame('https://example.org', (string)$request->getUri());
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

        return $request;
    }

    /**
     * @depends test_parsed_body_with_iterable_value
     *
     * @param ServerRequest $request
     */
    public function test_parsed_body_with_post_and_content_type(ServerRequest $request)
    {
        $_POST   = ['accept', 'this'];
        $request = $request->withHeader('Content-type', 'application/x-www-form-urlencoded; charset=utf-8');

        $request = $request->withParsedBody(['ignored', 'values']);
        $this->assertSame($_POST, $request->getParsedBody(), 'Supplied data is ignored per spec (Content-Type)');
    }

    public function test_parsed_body_throws_exception_on_unsupported_values()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported data provided (string), Expects NULL, array or iterable');
        $this->SUT->withParsedBody('junk');
    }

    public function test_return_posted_body()
    {
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        $_POST                        = ['key' => 'value'];

        $request = new ServerRequest;
        $this->assertSame($_POST, $request->getParsedBody(), 'Returns the _POST array');
    }

    public function test_return_posted_body_with_parsed_body()
    {
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        $_POST                        = ['key' => 'value'];

        $request  = new ServerRequest;
        $actual = $request->withParsedBody(['key' => 'value']);

        $this->assertNotSame($request, $actual, 'Response objects are immutable');
    }

    public function test_put_method_should_parse_the_php_input()
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_POST                     = ['foo' => 'bar'];

        $request = new ServerRequest;
        $this->assertSame(['foo' => 'bar'], $request->getParsedBody());
    }

    public function test_extra_methods()
    {
        $this->assertFalse($this->SUT->isXHR());
        $this->assertFalse($this->SUT->isSafeMethod());
        $this->assertFalse($this->SUT->isSecure());
    }

    public function test_xhr_with_wrong_sec_fetch_header()
    {
        $_SERVER['HTTP_SEC_FETCH_MODE'] = 'fubar';
        $request = new ServerRequest;
        $this->assertFalse($request->isXHR());
    }

    public function test_xhr_with_sec_fetch_header()
    {
        $_SERVER['HTTP_SEC_FETCH_MODE'] = 'cors';
        $request = new ServerRequest;
        $this->assertTrue($request->isXHR());
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

    public function test_should_return_empty_baseuri_if_host_is_unknown()
    {
        unset($_SERVER['SERVER_NAME'], $_SERVER['SERVER_ADDR'], $_SERVER['HTTP_HOST']);

        $request = new ServerRequest;
        $this->assertSame('', $request->getBaseUri());
    }

    public function test_should_add_object_attributes()
    {
        $request = new ServerRequest(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $request->getAttributes());

        $new = $request->withAttributes(['qux' => 'zim']);
        $this->assertSame(['foo' => 'bar', 'qux' => 'zim'], $new->getAttributes());
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

    public function test_parsed_body_if_method_is_post_with_provided_form_data()
    {
        $_POST = ['foo' => 'bar'];
        $this->setUp();
        $request = $this->SUT->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->assertSame($request->getParsedBody(), $_POST);
    }

    public function test_parsed_body_if_method_is_post_with_json_data()
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';

        $request = (new class extends ServerRequest
        {
            protected function getRawInput(): string
            {
                return '{"key":"value"}';
            }
        });

        $this->assertEquals(['key' => 'value'], $request->getParsedBody());
    }

    public function test_parsed_body_if_method_is_post_with_urlencoded_data()
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';

        $request = (new class extends ServerRequest
        {
            protected function getRawInput(): string
            {
                return 'key=value';
            }
        });

        $this->assertEquals(['key' => 'value'], $request->getParsedBody());
    }

    public function test_headers_with_content_type()
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json';

        $request = new ServerRequest;
        $this->assertEquals(
            'application/json',
            $request->getHeaderLine('content-type')
        );
    }

    protected function setUp(): void
    {
        $_SERVER['REQUEST_METHOD']  = 'POST';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['SERVER_NAME']     = 'example.org';
        $_SERVER['SERVER_PORT']     = 8080;
        $_SERVER['REQUEST_URI']     = '';
        $_SERVER['SCRIPT_FILENAME'] = '/index.php';

        $_SERVER['HTTP_HOST']          = 'example.org';
        $_SERVER['HTTP_IF_NONE_MATCH'] = '0163b37c-08e0-46f8-9aec-f31991bf6078-gzip';

        $this->SUT = new ServerRequest;
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        unset($_SERVER['HTTP_SEC_FETCH_MODE']);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI']    = '';

        $_POST     = [];
        unset($this->SUT);
    }
}
