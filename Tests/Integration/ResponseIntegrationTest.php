<?php

namespace Tests\Koded\Http\Integration;

use Koded\Http\ServerResponse;
use Psr\Http\Message\ResponseInterface;
use function Koded\Http\create_stream;

/**
 * @group integration
 */
class ResponseIntegrationTest extends \Http\Psr7Test\ResponseIntegrationTest
{
    protected $skippedTests = [
        'testStatusCodeInvalidArgument'        => 'Skipped, strict type implementation',
        'testWithHeaderInvalidArguments'       => 'Skipped, strict type implementation',
        'testWithAddedHeaderInvalidArguments'  => 'Skipped, strict type implementation',
    ];

    /**
     * @return ResponseInterface that is used in the tests
     */
    public function createSubject()
    {
        return new ServerResponse;
    }

    protected function buildStream($data)
    {
        return create_stream($data);
    }
}
