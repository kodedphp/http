<?php

namespace Tests\Koded\Http;

use Koded\Http\CallableStream;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CallableStreamTest extends TestCase
{
    use AssertionTestSupportTrait;

    public function test_should_initialize_the_stream()
    {
        $stream = new CallableStream(function() {});
        $this->assertFalse($stream->isWritable());
        $this->assertFalse($stream->isSeekable());
        $this->assertTrue($stream->isReadable());

        $this->assertSame(0, $stream->tell());
        $this->assertFalse($stream->eof());
    }

    public function test_should_reset_the_stream_on_destruct()
    {
        $callable = function() {};
        $stream   = new CallableStream($callable);
        $stream->__destruct();

        $properties = $this->getObjectProperties($stream, ['callable', 'position']);
        $this->assertSame(null, $properties['callable']);
        $this->assertSame(0, $properties['position']);
    }

    public function test_should_return_content_when_typecasted_to_string()
    {
        $stream = new CallableStream(function() {
            return 'lorem ipsum';
        });

        // not idempotent
        $this->assertSame('lorem ipsum', (string)$stream);
        $this->assertSame('', (string)$stream, 'After callable is consumed, the content is empty');
        $this->assertSame('', (string)$stream);
    }

    public function test_should_return_null_if_detached()
    {
        $stream = new CallableStream(function() {
            return 'lorem ipsum';
        });

        $result = $stream->detach();
        $this->assertNull($result);
    }

    public function test_should_return_null_for_stream_size()
    {
        $stream = new CallableStream(function() {
            return 'lorem ipsum';
        });

        $this->assertSame(null, $stream->getSize());
    }

    public function test_should_throw_exception_when_cannot_read()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot write to stream');

        $stream = new CallableStream(function() {
            return new \stdClass;
        });

        $stream->getContents();
    }

    public function test_should_return_empty_string_when_throws_exception_while_typecasted()
    {
        $stream = new CallableStream(function() {
            return new \stdClass;
        });

        $this->assertSame('', (string)$stream);
    }

    public function test_should_throw_exception_when_seek()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot seek in CallableStream');

        $stream = new CallableStream(function() {
            return 'lorem ipsum';
        });

        $stream->seek(20);
    }

    public function test_should_throw_exception_when_rewind()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot rewind the CallableStream');

        $stream = new CallableStream(function() {
            return 'lorem ipsum';
        });

        $stream->rewind();
    }

    public function test_should_throw_exception_when_write()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot write to CallableStream');

        $stream = new CallableStream(function() {
            return 'lorem ipsum';
        });

        $stream->write('');
    }

    public function test_should_read_the_whole_content_disregarding_the_length()
    {
        $stream = new CallableStream(function() {
            return 'lorem ipsum';
        });

        $this->assertSame('lorem ipsum', $stream->read(6));
    }

    public function test_should_yield_from_generator()
    {
        $stream = new CallableStream(function() {
            yield 1;
            yield 2;
            yield 3;

            return 4;
        });

        $this->assertSame('123', $stream->getContents());
    }

    public function test_metadata()
    {
        $stream = new CallableStream(function() {
            return 'lorem ipsum';
        });

        $metadata = $stream->getMetadata();
        $this->assertSame([], $metadata);
        $this->assertSame(null, $stream->getMetadata('junk'));
    }
}
