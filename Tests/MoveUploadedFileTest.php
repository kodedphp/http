<?php

namespace Koded\Http;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class MoveUploadedFileTest extends TestCase
{

    /** @var UploadedFile */
    private $SUT;

    private $file       = '/tmp/y4k9a7fm';
    private $targetPath = '/tmp/test-moved-to/filename.txt';

    public function test_stream_move_to()
    {
        $this->SUT->moveTo($this->targetPath);

        $this->assertAttributeSame(true, 'moved', $this->SUT);
        $this->assertFileExists($this->targetPath);
        $this->assertFileNotExists($this->file, 'Original file should be deleted after moving');
        $this->assertSame('hello', file_get_contents($this->targetPath));

        // After moving the file the stream is not available
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is not available, because the file was previously moved');
        $this->SUT->getStream();
    }

    public function test_stream_moved_file_cannot_be_moved_twice()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File is not available, because it was previously moved');

        $this->SUT->moveTo($this->targetPath);
        $this->SUT->moveTo($this->targetPath);
    }

    /**
     * @dataProvider invalidPathValues
     */
    public function test_stream_accept_string_for_target_path($targetPath)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('The provided path for moveTo operation is not valid');

        $this->SUT->moveTo($targetPath);
    }

    public function invalidPathValues()
    {
        return [
            [null],
            [true],
            [new \stdClass],
            [[]],
            [0],
            [1.2],
        ];
    }

    protected function setUp()
    {
        file_put_contents($this->file, 'hello');

        if (false === is_readable($this->file)) {
            $this->markTestSkipped('Unt test failed to create a test file');
        }

        $data      = include __DIR__ . '/fixtures/simple-file-array.php';
        $this->SUT = new UploadedFile($data['test']);
    }

    protected function tearDown()
    {
        @unlink($this->file);
        @rmdir(dirname($this->targetPath));
        parent::tearDown();
    }
}
