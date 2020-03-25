<?php

namespace Koded\Http;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

class StreamTest extends TestCase
{

    public function test_constructor_with_invalid_argument()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(StatusCode::UNPROCESSABLE_ENTITY);
        $this->expectExceptionMessage('The provided resource is not a valid stream resource');
        new Stream('');
    }

    public function test_should_initialize_the_stream_modes()
    {
        $stream = new Stream(fopen('php://temp', 'r'));
        $this->assertFalse($stream->isWritable());
        $this->assertTrue($stream->isSeekable());
        $this->assertTrue($stream->isReadable());
    }

    public function test_should_close_the_stream_on_destruct()
    {
        $resource = fopen('php://temp', 'r');
        $stream   = new Stream($resource);

        $stream->__destruct();

        $this->assertFalse(is_resource($resource));
        $this->assertAttributeSame(null, 'stream', $stream);
    }

    public function test_stream_should_return_content_when_typecasted_to_string()
    {
        $resource = fopen('php://temp', 'w');
        fwrite($resource, 'lorem ipsum');

        $stream = new Stream($resource);

        // idempotent
        $this->assertSame('lorem ipsum', (string)$stream);
        $this->assertSame('lorem ipsum', (string)$stream);
        $this->assertSame('lorem ipsum', (string)$stream);
    }

    public function test_stream_should_return_empty_string_when_throws_exception_while_typecasted()
    {
        $stream = new Stream(fopen('php://stderr', ''));
        $this->assertSame('', (string)$stream);
    }

    public function test_stream_should_return_empty_string_with_zero_content_length()
    {
        $resource = fopen('php://temp', 'r');
        $stream   = new Stream($resource);
        $this->assertSame('', $stream->read(0));
    }

    public function test_stream_should_throw_exception_on_read_when_stream_is_not_readable()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The stream is not readable');

        $stream = new Stream(fopen('php://stderr', ''));
        $stream->read(0);
    }

    public function test_stream_should_return_null_if_stream_is_already_detached()
    {
        $stream = new Stream(fopen('php://temp', 'r'));

        $result = $stream->detach();
        $this->assertNotNull($result, 'First detach() returns the underlying stream');

        $result = $stream->detach();
        $this->assertNull($result, 'Next detach() calls returns NULL for the underlying stream');
    }

    public function test_stream_should_return_the_stream_size()
    {
        $resource = fopen('php://temp', 'w');
        fwrite($resource, 'lorem ipsum');
        $stream = new Stream($resource);

        $this->assertSame(11, $stream->getSize());
    }

    public function test_stream_should_return_null_when_getting_size_with_empty_stream()
    {
        $stream = new Stream(fopen('php://temp', 'w'));
        $stream->detach();

        $this->assertNull($stream->getSize());
    }

    public function test_stream_tell_and_eof()
    {
        $resource = fopen('php://temp', 'w');
        fwrite($resource, 'lorem ipsum');
        $stream = new Stream($resource);

        $stream->seek(0);
        $this->assertSame(0, $stream->tell());

        $stream->seek(1);
        $this->assertSame(1, $stream->tell());

        $stream->eof();
        $this->assertFalse($stream->eof(), 'eof() do not move the stream pointer');
        $this->assertSame(1, $stream->tell(), 'Still on position 1');
    }

    public function test_stream_should_throw_exception_when_cannot_tell()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to find the position of the file pointer');

        $stream = new Stream(fopen('php://temp', 'r'));

        try {
            $stream->seek(20);
        } catch (Throwable $e) {
            // NOOP, continue
        }

        $this->assertSame(29, $stream->tell());
    }

    public function test_stream_should_throw_exception_when_cannot_seek()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to seek to file pointer');

        $resource = fopen('php://temp', 'w');
        fwrite($resource, 'lorem ipsum');
        $stream = new Stream($resource);
        $stream->seek(20);
    }

    public function test_stream_rewind()
    {
        $resource = fopen('php://temp', 'w');
        fwrite($resource, 'lorem ipsum');
        $stream = new Stream($resource);

        $this->assertSame(11, $stream->tell());

        $stream->rewind();
        $this->assertSame(0, $stream->tell());
    }

    public function test_stream_should_throw_exception_when_rewind_but_source_is_not_seekable()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The stream is not seekable');

        $stream = new Stream(fopen('php://stderr', ''));
        $stream->rewind();
    }

    public function test_stream_write()
    {
        $stream = new Stream(fopen('php://temp', 'w'));
        $bytes  = $stream->write('lorem ipsum');

        $this->assertSame(11, $bytes);
        $this->assertSame('', $stream->getContents(), 'Returns the remaining contents in the string');
    }

    public function test_stream_should_throw_exception_when_writing_and_stream_is_not_writable()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The stream is not writable');

        $stream = new Stream(fopen('php://stderr', ''));
        $stream->write('lorem ipsum');
    }

    public function test_stream_read()
    {
        $stream = new Stream(fopen('php://temp', 'w'));
        $stream->write('lorem ipsum');
        $stream->seek(6);

        $this->assertSame('ipsum', $stream->read(6));
    }

    public function test_stream_metadata()
    {
        $stream = new Stream(fopen('php://temp', 'w'));
        $stream->write('lorem ipsum');

        $metadata = $stream->getMetadata();
        $this->assertSame('PHP', $metadata['wrapper_type']);
        $this->assertSame('TEMP', $metadata['stream_type']);
        $this->assertSame('w+b', $metadata['mode']);
        $this->assertSame(0, $metadata['unread_bytes']);
        $this->assertSame(true, $metadata['seekable']);
        $this->assertSame('php://temp', $metadata['uri']);

        $this->assertSame('php://temp', $stream->getMetadata('uri'));
        $this->assertSame(null, $stream->getMetadata('junk'));
    }
}

