<?php

namespace Koded\Http;

use Psr\Http\Message\RequestInterface;

class RequestIntegrationTest extends \Http\Psr7Test\RequestIntegrationTest
{

    protected $skippedTests = [
        'testUri'                              => 'Skipped because of the host requirement',
        'testMethod'                           => 'Implementation uses constants where capitalization matters',
        'testMethodWithInvalidArguments'       => 'Does not make sense for strict type implementation',
        'testWithHeaderInvalidArguments'       => 'Does not make sense for strict type implementation',
        'testWithAddedHeaderInvalidArguments'  => 'Does not make sense for strict type implementation',
        'testWithAddedHeaderArrayValueAndKeys' => 'Skipped, the test is weird',
        'testWithAddedHeader'                  => 'Skipped, the test is weird',
        'testUriPreserveHost_NoHost_Host'      => 'Skipped because of the host requirement',
    ];

    /**
     * @return RequestInterface that is used in the tests
     */
    public function createSubject()
    {
        unset($_SERVER['HTTP_HOST']);
        return new ClientRequest('GET', '');
    }

}
