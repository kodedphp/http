<?php

namespace Koded\Http;

use Psr\Http\Message\ResponseInterface;

class ResponseIntegrationTest extends \Http\Psr7Test\ResponseIntegrationTest
{

    protected $skippedTests = [
        'testStatusCodeInvalidArgument'        => 'Does not make sense for strict type implementation',
        'testWithHeaderInvalidArguments'       => 'Does not make sense for strict type implementation',
        'testWithAddedHeaderInvalidArguments'  => 'Does not make sense for strict type implementation',
        'testWithAddedHeaderArrayValueAndKeys' => 'Skip this weird test',
        'testWithAddedHeader'                  => 'Skip this weird test',
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
