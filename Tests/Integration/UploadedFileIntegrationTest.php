<?php

namespace Koded\Http;

use Psr\Http\Message\UploadedFileInterface;

class UploadedFileIntegrationTest extends \Http\Psr7Test\UploadedFileIntegrationTest
{
    /**
     * @return UploadedFileInterface that is used in the tests
     */
    public function createSubject()
    {
        $filename = '.tmp/test.txt';
        touch($filename);
        file_put_contents($filename, 'Lorem ipsum');

        return new UploadedFile([
            'tmp_name' => $filename,
            'name'     => 'test.txt',
        ]);
    }

    protected function tearDown()
    {
        @unlink('.tmp/test.txt');
        parent::tearDown();
    }
}
