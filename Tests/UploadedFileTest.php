<?php

namespace Koded\Http;

use InvalidArgumentException;
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
        $this->assertSame(5, $this->SUT->getSize());
        $this->assertSame(UPLOAD_ERR_OK, $this->SUT->getError());
        $this->assertSame('w+b', $this->SUT->getStream()->getMetadata('mode'));

        $this->assertAttributeSame($this->file, 'file', $this->SUT);
        $this->assertAttributeSame(false, 'moved', $this->SUT);
    }

    /**
     * @dataProvider invalidTmpName
     */
    public function test_stream_should_pass_when_file_is_null($resource)
    {
        $this->expectException(InvalidArgumentException::class);

        $SUT = $this->prepareFile($resource);
        $this->assertInstanceOf(StreamInterface::class, $SUT->getStream());
    }

    public function test_move_to_invalid_target_path()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided path for moveTo operation is not valid');

        $this->SUT->moveTo('');
    }

    public function test_should_throw_exception_when_file_is_not_set()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The uploaded file is not supported');

        $file = (include __DIR__ . '/fixtures/simple-file-array.php')['test'];
        unset($file['tmp_name']);

        $SUT = new UploadedFile($file);
        $SUT->moveTo('/tmp/test-moved-to');
    }

    public function invalidTmpName()
    {
        return [
            [null],
            [true],
            [0],
            [1.2],
            [new \stdClass],
            [''],
            [[fopen('php://temp', 'r')]],
        ];
    }

    public function test_should_throw_exception_on_upload_error()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionCode(UPLOAD_ERR_CANT_WRITE);

        $file = (include __DIR__ . '/fixtures/simple-file-array.php')['test'];

        $file['error'] = UPLOAD_ERR_CANT_WRITE;

        $SUT = new UploadedFile($file);
        $SUT->moveTo('/tmp/test-moved-to/test-copy.txt');
    }

    protected function setUp()
    {
        touch($this->file);
        file_put_contents($this->file, 'hello');

        $files     = include __DIR__ . '/fixtures/simple-file-array.php';
        $this->SUT = new UploadedFile($files['test']);
    }

    protected function tearDown()
    {
        unlink($this->file);
        @unlink('/tmp/test-moved-to/filename.txt');
    }

    private function prepareFile($resource): UploadedFIle
    {
        $file             = (include __DIR__ . '/fixtures/simple-file-array.php')['test'];
        $file['tmp_name'] = $resource;

        return new UploadedFile($file);
    }
}
