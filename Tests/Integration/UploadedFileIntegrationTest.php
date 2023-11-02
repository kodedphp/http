<?php

namespace Tests\Koded\Http\Integration;

use Koded\Http\UploadedFile;
use Psr\Http\Message\UploadedFileInterface;

/**
 * @group integration
 */
class UploadedFileIntegrationTest extends \Http\Psr7Test\UploadedFileIntegrationTest
{
    protected $skippedTests = [
        'testGetSize' => 'This test is broken',
    ];

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

    protected function tearDown(): void
    {
        \Koded\Stdlib\rmdir('.tmp');
        parent::tearDown();
    }
}
