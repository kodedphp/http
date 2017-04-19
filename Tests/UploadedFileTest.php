<?php

namespace Koded\Http;

use function Koded\Stdlib\dump;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class UploadedFileTest extends TestCase
{

    /** @var UploadedFile */
    private $SUT;

    private $file = '/tmp/y4k9a7fm';

    public function test_constructor()
    {
        $this->assertSame('text/plain', $this->SUT->getClientMediaType());
        $this->assertSame('filename.txt', $this->SUT->getClientFilename());
        $this->assertSame(42, $this->SUT->getSize());
        $this->assertSame(UPLOAD_ERR_OK, $this->SUT->getError());
        $this->assertAttributeSame($this->file, 'file', $this->SUT);
        $this->assertAttributeSame(false, 'moved', $this->SUT);

        $this->assertAttributeInstanceOf(FileStream::class, 'stream', $this->SUT);
        $this->assertSame('r+', $this->SUT->getStream()->getMetadata('mode'));
    }

    public function test_stream_create_with_resource()
    {
        $file = (include __DIR__ . '/fixtures/simple-file-array.php')['test'];

        $SUT = new UploadedFile(fopen($this->file, 'r'), $file);
        $this->assertInstanceOf(StreamInterface::class, $SUT->getStream());
    }

    public function test_stream_create_with_instance()
    {
        $file = (include __DIR__ . '/fixtures/simple-file-array.php')['test'];

        $SUT = new UploadedFile(new FileStream($this->file, 'r'), $file);
        $this->assertInstanceOf(StreamInterface::class, $SUT->getStream());
    }

    public function test_stream_create_with_invalid_resource()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid resource provided for UploadedFile. Expected a file name, StreamInterface instance, or resource');

        $file = (include __DIR__ . '/fixtures/simple-file-array.php')['test'];
        new UploadedFile(null, $file);
    }

    public function test_move_to()
    {
        $targetPath = '/tmp/test-moved-to';

        $this->SUT->moveTo($targetPath);
        $this->assertFileExists($targetPath);
        $this->assertAttributeSame(true, 'moved', $this->SUT);

        unlink($targetPath);
    }

    public function test_move_cannot_be_moved_twice()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to get the stream because it was previously moved');

        $targetPath = '/tmp/test-moved-to';

        $this->SUT->moveTo($targetPath);
        $this->SUT->moveTo($targetPath);


        $this->assertFileExists($targetPath);
        $this->assertAttributeSame(true, 'moved', $this->SUT);

        unlink($targetPath);
    }

    public function test_move_to_invalid_target_path()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided path for moveTo operation cannot be empty');

        $this->SUT->moveTo('');
    }

    public function test_move_to_when_file_is_null()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to move the file to the requested location');

        $file = (include __DIR__ . '/fixtures/simple-file-array.php')['test'];
        $file['tmp_name'] = null;

        $SUT = new UploadedFile($this->file, $file);
        $SUT->moveTo('/tmp/test-moved-to');
    }

    public function test_move_to_when_cannot_move_the_file()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to move the file to the requested location');

        $file = (include __DIR__ . '/fixtures/simple-file-array.php')['test'];
        unset($file['tmp_name']);

        $SUT = new UploadedFile($this->file, $file);
        $SUT->moveTo('/tmp/test-moved-to');
    }

    public function test_should_throw_exception_on_upload_error()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionCode(UPLOAD_ERR_CANT_WRITE);

        $file = (include __DIR__ . '/fixtures/simple-file-array.php')['test'];
        $file['error'] = UPLOAD_ERR_CANT_WRITE;

        $SUT = new UploadedFile($this->file, $file);
        $SUT->getStream();
    }

    protected function setUp()
    {
        touch($this->file);
        $this->SUT = new UploadedFile($this->file, (include __DIR__ . '/fixtures/simple-file-array.php')['test']);
    }

    protected function tearDown()
    {
        @unlink($this->file);
    }
}
