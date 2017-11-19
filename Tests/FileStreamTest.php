<?php

namespace Koded\Http;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class FileStreamTest extends TestCase
{

    private $file = '/tmp/test';

    public function test_should_create_only_writable_stream()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The stream is not readable');

        $stream = new FileStream($this->file, 'w');
        $stream->write('hello world');

        $this->assertSame('w', $stream->getMetadata('mode'));
        $this->assertSame('', $stream->getContents());

        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isSeekable());
        $this->assertTrue($stream->isWritable());
    }

    public function test_should_create_read_write_stream_by_default()
    {
        $stream = new FileStream($this->file, 'w+');
        $stream->write('hello world');

        $this->assertSame('w+', $stream->getMetadata('mode'));
        $this->assertSame('hello world', $stream->getContents());

        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isSeekable());
        $this->assertTrue($stream->isWritable());
    }

    protected function tearDown()
    {
        @unlink($this->file);
    }
}
