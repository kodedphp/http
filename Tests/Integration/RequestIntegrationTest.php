<?php

namespace Koded\Http;

use Psr\Http\Message\RequestInterface;

class RequestIntegrationTest extends \Http\Psr7Test\RequestIntegrationTest
{
    protected $skippedTests = [
        'testUri'                              => 'Skipped because of the host requirement',
        'testMethod'                           => 'Implementation uses constants where capitalization matters',
        'testMethodWithInvalidArguments'       => 'Does not make sense for strict type implementation',
        'testUriPreserveHost_NoHost_Host'      => 'Skipped because of the host requirement',
    ];

    /**
     * @overridden The header is not merged as the test authors think it should
     */
    public function testWithAddedHeaderArrayValueAndKeys()
    {
        $message = $this->getMessage()->withAddedHeader('content-type', ['foo' => 'text/html']);
        $message = $message->withAddedHeader('content-type', ['foo' => 'text/plain', 'bar' => 'application/json']);
        $headerLine = $message->getHeaderLine('content-type');

        $this->assertRegExp('|text/plain|', $headerLine);
        $this->assertRegExp('|application/json|', $headerLine);
    }

    /**
     * @return RequestInterface that is used in the tests
     */
    public function createSubject()
    {
        unset($_SERVER['HTTP_HOST']);
        return new ClientRequest('GET', '');
    }
}
