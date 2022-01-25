<?php

namespace Tests\Koded\Http;

use InvalidArgumentException;
use Koded\Http\Uri;
use PHPUnit\Framework\TestCase;

class UriSettersTest extends TestCase
{
    private Uri $uri;

    protected function setUp(): void
    {
        $this->uri = new Uri('https://example.com:8080/foo/bar/#baz');
    }

    /**
     * @test
     */
    public function it_should_set_the_scheme()
    {
        $uri = $this->uri->withScheme('HTTP');
        $this->assertSame('http', $uri->getScheme());
        $this->assertNotSame($uri, $this->uri);
    }

    /**
     * @test
     */
    public function it_should_unset_the_scheme()
    {
        $uri = $this->uri->withScheme('');
        $this->assertSame('', $uri->getScheme());
        $this->assertNotSame($uri, $this->uri);
    }

    /**
     * @test
     */
    public function it_should_set_the_host()
    {
        $uri = $this->uri->withHost('example.net');
        $this->assertSame('example.net', $uri->getHost());
        $this->assertNotSame($uri, $this->uri);
    }

    /**
     * @test
     */
    public function it_should_unset_the_host()
    {
        $uri = $this->uri->withHost('');
        $this->assertSame('', $uri->getHost());
        $this->assertNotSame($uri, $this->uri);
    }

    /**
     * @test
     */
    public function it_should_set_the_userinfo()
    {
        $uri1 = $this->uri->withUserInfo('username');
        $this->assertSame('username', $uri1->getUserInfo(), 'Regular userinfo set');
        $this->assertNotSame($uri1, $this->uri);

        $uri2 = $uri1->withUserInfo('johndoe', 'pass');
        $this->assertNotSame($uri1, $uri2);
        $this->assertSame('johndoe:pass', $uri2->getUserInfo(), 'With userinfo password');
        $this->assertSame('username', $uri1->getUserInfo(), 'Not changed');

        $uri3 = $uri2->withUserInfo('', 'pass');
        $this->assertNotSame($uri3, $uri2);
        $this->assertSame('', $uri3->getUserInfo(), 'Without username the password os omitted');
    }

    /**
     * @test
     */
    public function it_should_unset_the_userinfo()
    {
        $uri = $this->uri->withHost('');
        $this->assertSame('', $uri->getHost());
        $this->assertNotSame($uri, $this->uri);
    }

    /**
     * @test
     */
    public function it_should_set_the_standard_port()
    {
        $uri = $this->uri->withPort(80);
        $this->assertSame(80, $uri->getPort());
        $this->assertNotSame($uri, $this->uri);
    }

    /**
     * @test
     */
    public function it_should_return_null_for_null_port_and_scheme()
    {
        $uri = (new Uri('/'))->withPort(null);
        $this->assertNull($uri->getPort());
    }

    /**
     * @test
     */
    public function it_should_set_the_nonstandard_port()
    {
        $uri = $this->uri->withPort(9000);
        $this->assertSame(9000, $uri->getPort());
        $this->assertNotSame($uri, $this->uri);
    }

    /**
     * @test
     */
    public function it_should_unset_the_port()
    {
        $uri = $this->uri->withPort(null);
        $this->assertNull($uri->getPort());
        $this->assertNotSame($uri, $this->uri);
    }

    /**
     * @test
     */
    public function it_should_throw_exception_for_invalid_port()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid port');

        $this->uri->withPort('junk');
    }

    /**
     * @test
     */
    public function it_should_set_the_path_as_is()
    {
        $uri = $this->uri->withPath('');
        $this->assertSame('', $uri->getPath());

        $uri = $this->uri->withPath('/foo');
        $this->assertSame('/foo', $uri->getPath());

        $uri = $this->uri->withPath('foo');
        $this->assertSame('foo', $uri->getPath());
    }

    /**
     * @test
     */
    public function it_should_redice_multiple_slashes_in_path_without_authority()
    {
        $uri = $this->uri->withPath('//fubar');
        $this->assertSame('/fubar', $uri->getPath());
    }

    /**
     * @test
     */
    public function it_should_keep_multiple_slashes_in_path_with_present_authority()
    {
        $uri = $this->uri
            ->withPath('//fubar')
            ->withUserInfo('user');

        $this->assertSame('//fubar', $uri->getPath());
    }

    /**
     * @test
     */
    public function it_should_set_the_fragment()
    {
        $uri = $this->uri->withFragment('#foo-1.2.0');
        $this->assertSame('foo-1.2.0', $uri->getFragment());

        $uri = $this->uri->withFragment('%23foo-1.2.0');
        $this->assertSame('foo-1.2.0', $uri->getFragment());

        $uri = $this->uri->withFragment('foo-1.2.0');
        $this->assertSame('foo-1.2.0', $uri->getFragment());
    }

    /**
     * @test
     */
    public function it_should_remove_the_fragment()
    {
        $uri = $this->uri->withFragment('');
        $this->assertSame('', $uri->getFragment());
    }
}
