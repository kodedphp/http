<?php

namespace Koded\Tests\Http;

use Koded\Http\HeaderTrait;
use PHPUnit\Framework\TestCase;

class MockHttpHeader
{
    use HeaderTrait;
}

class HeaderTraitTest extends TestCase
{

    /**
     * @var MockHttpHeader
     */
    private $SUT;

    public function test_get_header_not_set()
    {
        $this->assertSame([], $this->SUT->getHeader('foo'));
    }

    public function test_headers()
    {
        $this->assertSame([], $this->SUT->getHeaders());
    }

    public function test_header_line()
    {
        $this->assertSame('', $this->SUT->getHeaderLine('foo'));

        $response = $this->SUT->withHeader('foo', 1);
        $response = $response->withAddedHeader('foo', 'two');

        $this->assertSame('1,two', $response->getHeaderLine('foo'));

        $response = $this->SUT->withAddedHeader('bar', 'baz');
        $this->assertSame('baz', $response->getHeaderLine('bar'));
    }

    public function test_add_header_value()
    {
        $response = $this->SUT->withHeader('foo', 'bar');

        // $name is case-insensitive
        $this->assertSame(['bar'], $response->getHeader('foo'));
        $this->assertSame(['bar'], $response->getHeader('Foo'));

        return $response;
    }

    /**
     * @depends test_add_header_value
     *
     * @param MockHttpHeader $sut
     */
    public function test_has_header(MockHttpHeader $sut)
    {
        $this->assertTrue($sut->hasHeader('Foo'));
        $this->assertTrue($sut->hasHeader('foo'));
        $this->assertFalse($sut->hasHeader('zim'));
    }

    /**
     * @depends test_add_header_value
     *
     * @param MockHttpHeader $sut
     */
    public function test_delete_header(MockHttpHeader $sut)
    {
        $this->assertTrue($sut->hasHeader('Foo'));
        $response = $sut->withoutHeader('Foo');

        $this->assertTrue($sut->hasHeader('Foo'));
        $this->assertFalse($response->hasHeader('Foo'));
    }

    /**
     * @depends test_add_header_value
     *
     * @param MockHttpHeader $sut
     */
    public function test_delete_header_if_not_exist(MockHttpHeader $sut)
    {
        $this->assertFalse($sut->hasHeader('foobar'));
        $response = $sut->withoutHeader('foobar');
        $this->assertFalse($sut->hasHeader('foobar'), 'Should not throw exception if header is not set');
    }

    public function test_replace_headers()
    {
        $SUT = $this->SUT->withHeader('FOO_BAR', 'baz');
        $this->assertSame(['baz'], $SUT->getHeader('foo_bar'));

        $SUT = $SUT->replaceHeaders([
            'LONG_HEADER_NAME_1' => 'foo',
            'HEADER_2'           => 'bar'
        ]);

        $this->assertAttributeSame([
            'Long-Header-Name-1' => ['foo'],
            'Header-2'           => ['bar'],
        ], 'headers', $SUT);

        $this->assertAttributeSame([
            'long-header-name-1' => 'Long-Header-Name-1',
            'header-2'           => 'Header-2',
        ], 'headersMap', $SUT);
    }

    public function test_flattened_header()
    {
        $this->SUT = $this->SUT->withHeaders([
            'content-type'   => 'application/json',
            'content-length' => 1,
            'x-param'        => ['foo', 'bar'],
        ]);

        $this->assertSame([
            'Content-Type:application/json',
            'Content-Length:1',
            'X-Param:foo,bar'
        ],
            $this->SUT->getFlattenedHeaders(),
            'The spaces are removed, keys are capitalized');
    }

    public function test_empty_flattened_headers()
    {
        $this->assertSame([], $this->SUT->getFlattenedHeaders());
    }

    public function test_canonicalized_header()
    {
        $this->SUT = $this->SUT->withHeaders([
            'Content-type'   => 'application/json',
            'X-Param'        => ['foo', 'bar'],
            'content-length' => 1,
            'Accept' => '*/*'
        ]);

        $this->assertSame(
            'accept:*/*' . "\n" .
            'content-length:1' . "\n" .
            'content-type:application/json' . "\n" .
            'x-param:foo,bar',
            $this->SUT->getCanonicalizedHeaders());
    }

    public function test_empty_canonicalized_headers()
    {
        $this->assertSame('', $this->SUT->getCanonicalizedHeaders());
    }

    public function test_canonicalized_headers_with_names()
    {
        $this->SUT = $this->SUT->withHeaders([
            'Content-type'   => 'application/json',
            'X-Param'        => ['foo', 'bar'],
            'content-length' => 1,
            'Accept' => '*/*'
        ]);

        $this->assertSame(
            'content-length:1' . "\n" .
            'x-param:foo,bar',
            $this->SUT->getCanonicalizedHeaders(['content-length', 'x-param']));
    }

    public function test_canonicalized_headers_with_nonexistent_headers()
    {
        $this->assertSame("x-fubar:", $this->SUT->getCanonicalizedHeaders([
            'X_Fubar'
        ]), 'One matched header does not have a newline');

        $this->assertSame("x-fubar:\nx-param-1:", $this->SUT->getCanonicalizedHeaders([
            'X_Fubar',
            'X-PARAM-1'
        ]), 'The last element is without a newline');
    }

    public function test_normalizing_headers_key_and_value()
    {
        $this->SUT = $this->SUT->withHeaders([
            "HTTP/1.1 401 Authorization Required\r\n" => "\r\n",
            "cache-control\n" => " no-cache, no-store, must-revalidate, pre-check=0, post-check=0\r\n",
            "x-xss-protection\r\n" => "0 \r\n",
            " Nasty-\tHeader-\r\nName" => "weird\nvalue\r",
        ]);

        $this->assertSame([
            'Http/1.1 401 authorization required' => [''],
            'Cache-Control' => ['no-cache, no-store, must-revalidate, pre-check=0, post-check=0'],
            'X-Xss-Protection' => ['0'],
            "Nasty-Header-Name" => ["weird value"],
        ], $this->SUT->getHeaders());
    }

    protected function setUp()
    {
        $this->SUT = new MockHttpHeader;
    }
}
