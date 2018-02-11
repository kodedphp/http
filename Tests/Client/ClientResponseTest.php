<?php

namespace Koded\Http\Client;

use BadMethodCallException;
use Koded\Http\HttpStatus;
use PHPUnit\Framework\TestCase;

class ClientResponseTest extends TestCase
{

    public function test_send()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot send the client response.');
        $this->expectExceptionCode(HttpStatus::INTERNAL_SERVER_ERROR);

        (new ClientResponse)->send();
    }
}
