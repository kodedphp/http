<?php

namespace Tests\Koded\Http\Integration;

use Koded\Http\ServerRequest;
use Psr\Http\Message\RequestInterface;

/**
 * @group integration
 */
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
