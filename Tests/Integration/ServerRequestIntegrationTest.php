<?php

namespace Koded\Http;

use Psr\Http\Message\RequestInterface;

class ServerRequestIntegrationTest extends \Http\Psr7Test\ServerRequestIntegrationTest
{

    /**
     * @return RequestInterface that is used in the tests
     */
    public function createSubject()
    {
        unset($_SERVER['HTTP_HOST']);

        return new ServerRequest;
    }
}
