<?php

namespace Tests\Koded\Http;

use InvalidArgumentException;
use Koded\Http\ServerRequest;
use Koded\Http\UploadedFile;
use PHPUnit\Framework\TestCase;
use function Koded\Http\build_files_array;
use function Koded\Http\normalize_files_array;

class FilesTraitTest extends TestCase
{

    public function test_with_empty_files()
    {
        $request = new ServerRequest;
        $this->assertSame([], $request->getUploadedFiles());
    }

    public function test_simple_file()
    {
        $file = '/tmp/y4k9a7fm';
        touch($file);

        $_FILES = include __DIR__ . '/fixtures/simple-file-array.php';

        $request = new ServerRequest;
        $this->assertInstanceOf(UploadedFile::class, $request->getUploadedFiles()['test']);

        unlink($file);
        $_FILES = [];
    }

    public function test_nested_files()
    {
        $file1 = '/tmp/H3b00Ul2kq';
        $file2 = '/tmp/gt288ksoY3E';
        touch($file1);
        touch($file2);

        // the uber lame multiple _FILES

        $_FILES = [
            'test' => [
                'name'     => [
                    'filename1.txt',
                    'filename2.txt',
                ],
                'tmp_name' => [
                    $file1,
                    $file2,
                ],
                'size'     => [
                    42,
                    24,
                ],
                'error'    => [
                    UPLOAD_ERR_OK,
                    UPLOAD_ERR_OK,
                ],
                'type'     => [
                    'text/plain',
                    'text/plain',
                ]
            ]
        ];

        $request = new ServerRequest;
        $this->assertInstanceOf(UploadedFile::class, $request->getUploadedFiles()['test'][0]);
        $this->assertInstanceOf(UploadedFile::class, $request->getUploadedFiles()['test'][1]);

        unlink($file1);
        unlink($file2);
        $_FILES = [];
    }

    public function test_with_ridiculously_nested_file()
    {
        $file1 = '/tmp/php3liuXo';
        $file2 = '/tmp/phpYAvEdT';
        touch($file1);
        touch($file2);
        $_FILES = include __DIR__ . '/fixtures/very-complicated-files-array.php';

        $request = new ServerRequest;
        $this->assertInstanceOf(UploadedFile::class, $request->getUploadedFiles()['test'][0]['a']['b']['c']);
        $this->assertInstanceOf(UploadedFile::class, $request->getUploadedFiles()['test'][1]['a']['b']['c']);

        unlink($file1);
        unlink($file2);
        $_FILES = [];
    }

    public function test_with_file_instance()
    {
        $file = '/tmp/y4k9a7fm';
        touch($file);

        $_FILES = include __DIR__ . '/fixtures/simple-file-array.php';

        $request = new ServerRequest;
        $this->assertInstanceOf(UploadedFile::class, $request->getUploadedFiles()['test']);

        unlink($file);
        $_FILES = [];
    }

    public function test_with_files_replacement()
    {
        $file = '/tmp/y4k9a7fm';
        touch($file);

        $request = new ServerRequest;
        $request = $request->withUploadedFiles(include __DIR__ . '/fixtures/simple-file-array.php');
        $files   = $request->getUploadedFiles();

        $this->assertIsArray($files);
        $this->assertInstanceOf(UploadedFile::class, $files['test']);

        unlink($file);
        $_FILES = [];
    }

    public function test_files_array_with_file_instance()
    {
        $normalized = normalize_files_array(include __DIR__ . '/fixtures/very-complicated-files-array.php');

        $normalized['test'][0]['a']['b']['c'] = new UploadedFile([
            'tmp_name' => __DIR__ . '/fixtures/very-complicated-files-array.php',
        ]);

        $this->assertInstanceOf(UploadedFile::class, build_files_array($normalized)['test'][0]['a']['b']['c']);
    }

    public function test_files_array_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The uploaded file is not supported');

        new UploadedFile([]);
    }
}
