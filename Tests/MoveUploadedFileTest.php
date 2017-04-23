<?php

namespace Koded\Http;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class MoveUploadedFileTest extends TestCase
{

    /** @var UploadedFile */
    private $SUT;

    private $file = '/tmp/y4k9a7fm';

    public function test_move_to()
    {
        [$targetPath, $expected] = $this->prepareToMove();
        $this->SUT->moveTo($targetPath);

        $this->assertFileExists($targetPath);
        $this->assertAttributeSame(true, 'moved', $this->SUT);
        $this->assertFileExists($expected);
        $this->assertFileNotExists($this->file, 'Original file should be deleted after moving');

        // cleanup
        unlink($expected);
        rmdir($targetPath);

        // After moving the file the stream is not available
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to get the stream because it was previously moved');
        $this->SUT->getStream();
    }

    public function test_move_cannot_be_moved_twice()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to get the stream because it was previously moved');

        [$targetPath, $expected] = $this->prepareToMove();
        $this->SUT->moveTo($targetPath);

        // cleanup
        unlink($expected);
        rmdir($targetPath);

        $this->SUT->moveTo($targetPath);
    }

    public function test_move_to_when_file_is_null()
    {
        $this->expectException(UploadedFileException::class);
        $this->expectExceptionMessage('No file');

        $file = (include __DIR__ . '/fixtures/simple-file-array.php')['test'];
        $file['tmp_name'] = null;

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

    /**
     * @return array
     */
    private function prepareToMove(): array
    {
        $targetPath = '/tmp/test-moved-to';
        $expected   = $targetPath . '/filename.txt';

        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0777, true);
        }

        return [$targetPath, $expected];
    }
}
