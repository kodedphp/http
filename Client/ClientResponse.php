<?php

/*
 * This file is part of the Koded package.
 *
 * (c) Mihail Binev <mihail@kodeart.com>
 *
 * Please view the LICENSE distributed with this source code
 * for the full copyright and license information.
 *
 */

namespace Koded\Http\Client;

use BadMethodCallException;
use Koded\Http\HttpStatus;
use Koded\Http\ServerResponse;

class ClientResponse extends ServerResponse
{

    public function send(): string
    {
        throw new BadMethodCallException(self::E_CLIENT_RESPONSE_SEND, HttpStatus::INTERNAL_SERVER_ERROR);
    }
}
