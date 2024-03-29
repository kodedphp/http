<?php

namespace Tests\Koded\Http;

use InvalidArgumentException;
use Koded\Http\CallableStream;
use Koded\Http\FileStream;
use Koded\Http\Stream;
use PHPUnit\Framework\TestCase;
use function Koded\Http\build_files_array;
use function Koded\Http\create_stream;
use function Koded\Http\normalize_files_array;
use function Koded\Http\stream_copy;
use function Koded\Http\stream_to_string;

class FunctionsTest extends TestCase
{
    public function test_create_stream_from_string()
    {
        $stream = create_stream('lorem ipsum');
        $this->assertSame('lorem ipsum', (string)$stream);
    }

    public function test_create_stream_from_stream_instance()
    {
        $stream = create_stream(new Stream(fopen('php://temp', 'r')));
        $this->assertSame('', (string)$stream);
    }

    public function test_create_stream_from_resource()
    {
        $stream = create_stream(fopen('php://temp', 'r'));
        $this->assertSame('', (string)$stream);
    }

    public function test_create_stream_with_null_argument()
    {
        $stream = create_stream(null);
        $this->assertSame('', (string)$stream);
    }

    public function test_create_stream_from_object()
    {
        $object = new class
        {
            public function __toString()
            {
                return 'Lorem ipsum dolor sit amet';
            }
        };

        $stream = create_stream($object);
        $this->assertSame('Lorem ipsum dolor sit amet', (string)$stream);
    }

    public function test_create_stream_from_callable()
    {
        $callable = function() {
            return 'foo bar baz';
        };

        $stream = create_stream($callable);
        $this->assertInstanceOf(CallableStream::class, $stream);

        $this->assertSame('foo bar baz', (string)$stream);
        $this->assertSame('', (string)$stream, 'After callable is consumed, the content is empty');
        $this->assertSame(11, $stream->tell());

        $stream->close();
        $this->assertSame(0, $stream->tell());
    }

    public function test_create_stream_from_generator()
    {
        $generator = function() {
            yield 'foo bar baz';
            yield ' 42';

            return 'w000000000t';
        };

        $stream = create_stream($generator);

        $this->assertSame('foo bar baz 42', (string)$stream);
        $this->assertSame('', (string)$stream, 'After callable is consumed, the content is empty');
        $this->assertSame(14, $stream->tell());

        $stream->close();
        $this->assertSame(0, $stream->tell());
    }

    public function test_create_stream_throws_exception_on_unsupported_type()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to create a stream. Expected a file name, StreamInterface instance, or a resource. Given object type');

        $stream = create_stream(new \stdClass);
        $this->assertSame('Lorem ipsum dolor sit amet', (string)$stream); // FIXME what!?
    }

    public function test_stream_copy()
    {
        $source      = __DIR__ . '/../LICENSE';
        $destination = '/tmp/LICENSE-copy.json';

        $sourceStream      = create_stream(new FileStream($source));
        $destinationStream = new FileStream($destination, 'w+');

        $bytes = stream_copy($sourceStream, $destinationStream);
        $this->assertGreaterThan(0, $bytes);

        unlink($destination);
    }

    public function test_stream_to_string()
    {
        $file   = __DIR__ . '/../LICENSE';
        $stream = create_stream(new FileStream($file, 'r'));

        $this->assertSame(file_get_contents($file), stream_to_string($stream));
    }

    public function test_files_array_normalization()
    {
        $normalized = normalize_files_array(include __DIR__ . '/fixtures/very-complicated-files-array.php');
        $this->assertEquals($normalized, include __DIR__ . '/fixtures/very-complicated-files-array-normalized.php');
    }

    public function test_files_array_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to process the uploaded files. Invalid file structure provided');

        build_files_array(['']);
    }
}
