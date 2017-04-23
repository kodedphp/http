<?php

namespace Koded\Http;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class UploadedFileTest extends TestCase
{

    /** @var UploadedFile */
    private $SUT;

    private $file = '/tmp/y4k9a7fm';

    public function test_constructor()
    {
        $this->assertSame('text/plain', $this->SUT->getClientMediaType());
        $this->assertSame('filename.txt', $this->SUT->getClientFilename());
        $this->assertSame(5, $this->SUT->getSize());
        $this->assertSame(UPLOAD_ERR_OK, $this->SUT->getError());
        $this->assertAttributeSame($this->file, 'file', $this->SUT);
        $this->assertAttributeSame(false, 'moved', $this->SUT);

        $this->assertAttributeInstanceOf(StreamInterface::class, 'stream', $this->SUT);
        $this->assertSame('w+b', $this->SUT->getStream()->getMetadata('mode'));
    }

    public function test_stream_create_with_resource()
    {
        $file = (include __DIR__ . '/fixtures/simple-file-array.php')['test'];

        $file['tmp_name'] = fopen($this->file, 'r');

        $SUT = new UploadedFile($file);
        $this->assertInstanceOf(StreamInterface::class, $SUT->getStream());
    }

    public function test_stream_create_with_instance()
    {
        $file = (include __DIR__ . '/fixtures/simple-file-array.php')['test'];

        $file['tmp_name'] = new FileStream($this->file, 'r');

        $SUT = new UploadedFile($file);
        $this->assertInstanceOf(FileStream::class, $SUT->getStream());
    }

    public function test_stream_create_with_invalid_resource()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to create a stream. Expected a file name, StreamInterface instance, or a resource. Given boolean type');

        $file = (include __DIR__ . '/fixtures/simple-file-array.php')['test'];

        $file['tmp_name'] = true;
        new UploadedFile($file);
    }

    public function test_move_to_invalid_target_path()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided path for moveTo operation is not valid');

        $this->SUT->moveTo('');
    }

    public function test_should_throw_exception_when_file_is_not_set()
    {
        $this->expectExceptionMessage('No file');

        $file = (include __DIR__ . '/fixtures/simple-file-array.php')['test'];
        unset($file['tmp_name']);

        $SUT = new UploadedFile($file);
        $SUT->moveTo('/tmp/test-moved-to');
    }

    public function test_when_stream_is_not_available()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The stream is not available for the uploaded file');

        $file = (include __DIR__ . '/fixtures/simple-file-array.php')['test'];
        unset($file['tmp_name']);

        $SUT = new UploadedFile($file);
        $SUT->getStream();
    }

    public function test_should_throw_exception_on_upload_error()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionCode(UPLOAD_ERR_CANT_WRITE);

        $file = (include __DIR__ . '/fixtures/simple-file-array.php')['test'];

        $file['error'] = UPLOAD_ERR_CANT_WRITE;

        $SUT = new UploadedFile($file);
        $SUT->moveTo('/tmp/test-moved-to');
    }

    protected function setUp()
    {
        touch($this->file);
        file_put_contents($this->file, 'hello');

        $data = include __DIR__ . '/fixtures/simple-file-array.php';

        $data['test']['tmp_name'] = $this->file;

        $this->SUT = new UploadedFile($data['test']);
    }

    protected function tearDown()
    {
        @unlink($this->file);
    }
}
