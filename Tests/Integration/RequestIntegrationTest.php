<?php

namespace Koded\Http;

use Psr\Http\Message\RequestInterface;

class RequestIntegrationTest extends \Http\Psr7Test\RequestIntegrationTest
{

    /** @var RequestInterface */
    protected $request;

    protected $skippedTests = [
        'testUri'                              => 'Skipped because og the host requirement',
        'testMethod'                           => 'Implementation uses constants (capitalisation matters)',
        'testMethodWithInvalidArguments'       => 'Does not make sense for strict type implementation',
        'testWithHeaderInvalidArguments'       => 'Does not make sense for strict type implementation',
        'testWithAddedHeaderInvalidArguments'  => 'Does not make sense for strict type implementation',
        'testWithAddedHeaderArrayValueAndKeys' => 'Skip, the test id weird',
        'testWithAddedHeader'                  => 'Skip, the test id weird',
        'testUriPreserveHost_NoHost_Host'      => 'Skipped because og the host requirement',
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
