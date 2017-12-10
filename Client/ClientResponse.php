<?php

namespace Koded\Http\Client;

use BadMethodCallException;
use Koded\Http\HttpStatus;
use Koded\Http\ServerResponse;

class ClientResponse extends ServerResponse
{

    public function send(): string
    {
        throw new BadMethodCallException('Cannot send the client response.', HttpStatus::INTERNAL_SERVER_ERROR);
    }
}
