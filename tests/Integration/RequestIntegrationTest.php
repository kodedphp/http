<?php

namespace Tests\Koded\Http\Integration;

use Koded\Http\ClientRequest;
use Koded\Http\Interfaces\HttpMethod;
use Psr\Http\Message\RequestInterface;

/**
 * @group integration
 */
class RequestIntegrationTest extends \Http\Psr7Test\RequestIntegrationTest
{
    protected $skippedTests = [
        'testUri'                              => 'Skipped, URI typecast to string returns the absolute URI, not the path',
        'testMethodIsCaseSensitive'            => 'Implementation uses constants where capitalization matters',
        'testMethodWithInvalidArguments'       => 'Skipped, strict type implementation',
        'testWithHeaderInvalidArguments'       => 'Skipped, strict type implementation',
        'testWithAddedHeaderInvalidArguments'  => 'Skipped, strict type implementation',

        'testGetRequestTargetInOriginFormNormalizesUriWithMultipleLeadingSlashesInPath' => 'Is this test correct?',
    ];

    /**
     * @return RequestInterface that is used in the tests
     */
    public function createSubject()
    {
        unset($_SERVER['HTTP_HOST']);
        return new ClientRequest(HttpMethod::GET, '');
    }
}
