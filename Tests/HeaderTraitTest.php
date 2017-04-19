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

    public function testGetHeaderNotSet()
    {
        $this->assertSame([], $this->SUT->getHeader('foo'));
    }

    public function testHeaders()
    {
        $this->assertSame([], $this->SUT->getHeaders());
    }

    public function test_()
    {
        $this->assertSame('', $this->SUT->getHeaderLine('foo'));

        $response = $this->SUT->withHeader('foo', 1);
        $response = $response->withAddedHeader('foo', 'two');

        $this->assertSame('1,two', $response->getHeaderLine('foo'));

        $response = $this->SUT->withAddedHeader('bar', 'baz');
        $this->assertSame('baz', $response->getHeaderLine('bar'));
    }

    public function testAddHeaderValue()
    {
        $response = $this->SUT->withHeader('foo', 'bar');

        // $name is case-insensitive
        $this->assertSame(['bar'], $response->getHeader('foo'));
        $this->assertSame(['bar'], $response->getHeader('Foo'));

        return $response;
    }

    /**
     * @depends testAddHeaderValue
     *
     * @param MockHttpHeader $sut
     */
    public function testHasHeader(MockHttpHeader $sut)
    {
        $this->assertTrue($sut->hasHeader('Foo'));
        $this->assertTrue($sut->hasHeader('foo'));
        $this->assertFalse($sut->hasHeader('zim'));
    }

    /**
     * @depends testAddHeaderValue
     *
     * @param MockHttpHeader $sut
     */
    public function testDeleteHeader(MockHttpHeader $sut)
    {
        $this->assertTrue($sut->hasHeader('Foo'));
        $response = $sut->withoutHeader('Foo');

        $this->assertTrue($sut->hasHeader('Foo'));
        $this->assertFalse($response->hasHeader('Foo'));
    }

    protected function setUp()
    {
        $this->SUT = new MockHttpHeader;
    }
}
