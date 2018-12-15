<?php

namespace Koded\Http;

use InvalidArgumentException;
use function Koded\Stdlib\dump;
use PHPUnit\Framework\TestCase;

class UriGettersTest extends TestCase
{

    /**
     * @test
     */
    public function it_should_throw_exception_if_cannot_parse_the_uri()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Please provide a valid URI');
        $this->expectExceptionCode(StatusCode::BAD_REQUEST);

        new Uri('scheme://host:junk');
    }

    /**
     * @test
     */
    public function it_should_construct_as_expected()
    {
        // @todo the URI should be more complex
        $uri = new Uri('https://example.net/#foo');

        $this->assertSame('https', $uri->getScheme());
        $this->assertEmpty($uri->getUserInfo());
        $this->assertSame('example.net', $uri->getHost());
        $this->assertSame(null, $uri->getPort());
        $this->assertSame('/', $uri->getPath());
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('foo', $uri->getFragment());
    }

    /**
     * @test
     */
    public function it_should_lowercase_the_scheme_and_host()
    {
        $uri = new Uri('HTTPS://EXAMPLE.COM:80');
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('example.com', $uri->getHost());
    }

    /**
     * @test
     */
    public function it_should_not_return_a_standard_port()
    {
        $this->assertNull((new Uri('https://example.com:443'))->getPort(), 'Standard ports are not returned');
        $this->assertNull((new Uri('https://example.com:80'))->getPort(), 'Standard ports are not returned');
    }

    /**
     * @test
     */
    public function it_should_return_the_port()
    {
        $this->assertSame(8080, (new Uri('https://example.com:8080'))->getPort());
    }

    /**
     * @test
     */
    public function it_should_not_decode_encoded_path()
    {
        $uri = new Uri('https://example.com/foo%252/index.php');
        $this->assertSame('/foo%252', $uri->getPath(), 'index.php should be removed');
    }

    /**
     * @test
     */
    public function it_should_remove_index_php()
    {
        $uri = new Uri('foo/index.php');
        $this->assertSame('foo', $uri->getPath(), 'index.php should be removed');
    }

    /**
     * @test
     */
    public function it_should_create_instance_without_url()
    {
        $uri = new Uri('');
        $this->assertSame('', $uri->getPath());
    }

    /**
     * @test
     */
    public function it_should_return_the_query_string()
    {
        $uri = new Uri('https://example.net?foo=bar&qux');

        $this->assertInternalType('string', $uri->getQuery());
        $this->assertEquals('foo=bar&qux', $uri->getQuery());
    }

    /**
     * @test
     */
    public function it_should_replace_the_query_string()
    {
        $uri = new Uri('https://example.net?foo=bar&qux');
        $this->assertEquals('foo=bar&qux', $uri->getQuery());

        $uri = $uri->withQuery('');
        $this->assertEquals('', $uri->getQuery(), 'Remove query string');

        $uri = $uri->withQuery('page=1&limit=10');
        $this->assertEquals('page=1&limit=10', $uri->getQuery());
    }

    /**
     * @test
     */
    public function it_should_throw_exception_on_invalid_query_string()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided query string is invalid');

        $uri = new Uri('');
        $uri->withQuery(new \stdClass);
    }

    /**
     * @test
     */
    public function it_should_parse_credentials_and_exclude_the_standard_port()
    {
        $uri = new Uri('https://username:password@example.org:80');
        $this->assertSame('username:password@example.org', $uri->getAuthority());
        $this->assertSame('username:password', $uri->getUserInfo());

        // without password
        $uri = new Uri('https://username@example.org');
        $this->assertSame('username', $uri->getUserInfo());
        $this->assertSame('username@example.org', $uri->getAuthority());
    }

    /**
     * @test
     */
    public function it_should_parse_credentials_and_include_the_port()
    {
        $uri = new Uri('https://username:password@example.org:123');
        $this->assertSame('username:password@example.org:123', $uri->getAuthority());
        $this->assertSame('username:password', $uri->getUserInfo());

        // without password
        $uri = new Uri('https://username@example.org:8080');
        $this->assertSame('username', $uri->getUserInfo());
        $this->assertSame('username@example.org:8080', $uri->getAuthority());
    }

    /**
     * @test
     */
    public function it_should_handle_empty_credentials()
    {
        $uri = new Uri('https://example.com');
        $this->assertEmpty($uri->getUserInfo());
    }

    /**
     * @test
     */
    public function it_should_set_without_decoding_the_encoded_fragment()
    {
        $uri = new Uri('#fubar%26zim#qux');
        $this->assertSame('fubar%26zim#qux', $uri->getFragment());
    }

    /**
     * @test
     */
    public function it_should_add_slash_after_host_when_typecast_to_string()
    {
        $this->markTestSkipped('Need more info');

        $uri = new Uri('https://example.org');
        $this->assertSame('https://example.org/', (string)$uri);
    }

    /**
     * @test
     */
    public function it_should_deal_with_the_slash_for_consuming_libraries()
    {
        $uri = new Uri('https://example.org');
        $this->assertSame('', $uri->getPath());

        $uri = new Uri('https://example.org/');
        $this->assertSame('/', $uri->getPath());
    }

    /**
     * @test
     */
    public function it_should_return_empty_string_for_authority_without_userinfo()
    {
        $uri = new Uri('https://example.org');
        $this->assertSame('', $uri->getAuthority());
    }


    /**
     * @test
     */
    public function it_should_create_an_expected_representation_when_typecast_to_string()
    {
        $template = 'https://example.org:123/foo/bar?a[]=1&a[]=2&foo=bar&qux#frag';
        $uri      = new Uri($template);
        $this->assertSame($template, (string)$uri);

        $template = 'https://username:password@example.org/foo/bar?a[]=1&a[]=2&foo=bar&qux#frag';
        $uri      = new Uri($template);
        $this->assertSame($template, (string)$uri);

        $template = '/foo/bar?a[]=1&a[]=2&foo=bar&qux#frag';
        $uri      = new Uri($template);
        $this->assertSame($template, (string)$uri);

        // - If the path is rootless and an authority is present,
        // the path MUST be prefixed with "/"
        $template = 'foo/bar';
        $uri      = new Uri($template);
        $uri      = $uri->withUserInfo('username');
        $this->assertSame("username@/$template", (string)$uri);

        // - If the path is starting with more than one "/" and no authority is
        // present, the starting slashes MUST be reduced to one
        $template = 'http://localhost///foo/bar';
        $uri      = new Uri($template);
        $this->assertSame('http://localhost/foo/bar', (string)$uri);
    }
}
