<?php

namespace Koded\Http;

use Psr\Http\Message\RequestInterface;

class RequestIntegrationTest extends \Http\Psr7Test\RequestIntegrationTest
{

    /** @var RequestInterface */
    protected $request;

    protected $skippedTests = [
        'testUri'                              => 'Skipped because of the host requirement',
        'testMethod'                           => 'Implementation uses constants (capitalisation matters)',
        'testMethodWithInvalidArguments'       => 'Does not make sense for strict type implementation',
        'testWithHeaderInvalidArguments'       => 'Does not make sense for strict type implementation',
        'testWithAddedHeaderInvalidArguments'  => 'Does not make sense for strict type implementation',
        'testWithAddedHeaderArrayValueAndKeys' => 'Skip, the test is weird',
        'testWithAddedHeader'                  => 'Skip, the test is weird',
        'testUriPreserveHost_NoHost_Host'      => 'Skipped because of the host requirement',
    ];

    /**
     * @return RequestInterface that is used in the tests
     */
    public function createSubject()
    {
        unset($_SERVER['HTTP_HOST']);
        return $this->request = new ClientRequest('GET', '');
    }

}
