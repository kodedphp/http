<?php

namespace Koded\Http;

use function Koded\Stdlib\dump;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FileStreamTest extends TestCase
{

    public function test_should_create_only_writable_stream()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The stream is not readable');

        $stream = new FileStream('/tmp/mixa', 'w');
        $stream->write('hello world');

        $this->assertSame('w', $stream->getMetadata('mode'));
        $this->assertSame('', $stream->getContents());

        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isSeekable());
        $this->assertTrue($stream->isWritable());

    }

    public function test_should_create_read_write_stream_by_default()
    {
        $stream = new FileStream('/tmp/mixa', 'w+');
        $stream->write('hello world');

        $this->assertSame('w+', $stream->getMetadata('mode'));
        $this->assertSame('hello world', $stream->getContents());

        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isSeekable());
        $this->assertTrue($stream->isWritable());
    }
}
