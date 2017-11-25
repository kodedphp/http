<?php

namespace Koded\Tests\Http;

use Koded\Http\MessageTrait;
use Koded\Http\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class MessageTraitTest extends TestCase
{

    /**
     * @var TestMessage
     */
    private $SUT;

    public function test_should_deal_with_unsupported_protocol()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported HTTP protocol version 3.0');
        (new TestMessage)->withProtocolVersion('3.0');
    }

    public function test_should_set_supported_protocol_versions()
    {
        $this->assertSame('1.0', $this->SUT->withProtocolVersion('1.0')->getProtocolVersion());
        $this->assertSame('1.1', $this->SUT->withProtocolVersion('1.1')->getProtocolVersion());
        $this->assertSame('2.0', $this->SUT->withProtocolVersion('2.0')->getProtocolVersion());
    }

    public function test_should_always_return_instance_of_stream()
    {
        $this->assertInstanceOf(StreamInterface::class, $this->SUT->getBody());
        $this->assertSame('', (string)$this->SUT->getBody(), 'Returns an empty stream if not initialized or created');
    }

    public function test_should_assign_a_new_body_object()
    {
        $stream = new Stream(fopen('php://temp', 'r'));
        $instance = $this->SUT->withBody($stream);

        $this->assertNotSame($instance, $this->SUT);
        $this->assertSame($stream, $instance->getBody());
    }

    public function test_magic_set_is_disabled()
    {
        $this->SUT->fubar = 'this-is-not-set';
        $this->assertFalse(property_exists($this->SUT, 'fubar'));
    }

    protected function setUp()
    {
        $this->SUT = new TestMessage;
    }
}


class TestMessage
{
    use MessageTrait;

    public function setContent($content)
    {
        $this->content = $content;
    }
}
