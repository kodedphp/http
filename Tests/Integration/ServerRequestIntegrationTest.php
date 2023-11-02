<?php

namespace Tests\Koded\Http\Integration;

use Koded\Http\ServerRequest;
use Psr\Http\Message\RequestInterface;

/**
 * @group integration
 */
class ServerRequestIntegrationTest extends \Http\Psr7Test\ServerRequestIntegrationTest
{
    protected $skippedTests = [
        'testMethodIsCaseSensitive' => 'Skipped, using enums for HTTP methods',
        'testMethodWithInvalidArguments' => 'Skipped, strict type implementation',

        'testGetRequestTargetInOriginFormNormalizesUriWithMultipleLeadingSlashesInPath' => 'Is this test correct?',

        'testMethod' => 'Skipping for now ...',
    ];

    /**
     * @return RequestInterface that is used in the tests
     */
    public function createSubject()
    {
        unset($_SERVER['HTTP_HOST']);
        return new ServerRequest;
    }
}
