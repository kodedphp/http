<?php

namespace Koded\Tests\Http;

use Koded\Http\MessageTrait;
use Koded\Http\Stream;
use PHPUnit\Framework\TestCase;

class MessageTraitTest extends TestCase
{

    /**
     * @var TestMessage
     */
    private $SUT;

    /**
     * @test
     */
    public function it_should_deal_with_unsupported_protocol()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported HTTP protocol version 3.0');
        (new TestMessage)->withProtocolVersion('3.0');
    }

    /**
     * @test
     */
    public function it_should_set_supported_protocol_versions()
    {
        $this->assertSame('1.0', $this->SUT->withProtocolVersion('1.0')->getProtocolVersion());
        $this->assertSame('1.1', $this->SUT->withProtocolVersion('1.1')->getProtocolVersion());
        $this->assertSame('2', $this->SUT->withProtocolVersion('2')->getProtocolVersion());
    }

    /**
     * @test
     */
    public function it_should_return_null_body_by_default()
    {
        $this->assertNull($this->SUT->getBody());
    }

    /**
     * @test
     */
    public function it_should_assign_a_new_body_object()
    {
        $stream = new Stream(fopen('php://temp', 'r'));
        $instance = $this->SUT->withBody($stream);

        $this->assertNotSame($instance, $this->SUT);
        $this->assertSame($stream, $instance->getBody());
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
