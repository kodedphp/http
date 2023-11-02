<?php

namespace Tests\Koded\Http;

use Koded\Http\UploadedFile;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MoveUploadedFileTest extends TestCase
{
    use AssertionTestSupportTrait;

    private UploadedFile $SUT;
    private string $file       = '/tmp/y4k9a7fm';
    private string $targetPath = '/tmp/test-moved-to/filename.txt';

    public function test_stream_move_to()
    {
        $this->SUT->moveTo($this->targetPath);

        $movedValue = $this->getObjectProperty($this->SUT, 'moved');
        $this->assertSame(true, $movedValue);

        $this->assertFileExists($this->targetPath);
        $this->assertFileDoesNotExist($this->file, 'Original file should be deleted after moving');
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

    protected function setUp(): void
    {
        file_put_contents($this->file, 'hello');

        if (false === is_readable($this->file)) {
            $this->markTestSkipped('Unt test failed to create a test file');
        }

        $data      = include __DIR__ . '/fixtures/simple-file-array.php';
        $this->SUT = new UploadedFile($data['test']);
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        @rmdir(dirname($this->targetPath));
        parent::tearDown();
    }
}
