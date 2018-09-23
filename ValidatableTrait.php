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

namespace Koded\Http;

use Koded\Http\Interfaces\{HttpInputValidator, Response};
use Koded\Stdlib\Immutable;

trait ValidatableTrait
{

    public function validate(HttpInputValidator $validator): ?Response
    {
        $body = new Immutable($this->getParsedBody() ?? []);

        if (0 === $body->count()) {
            return null;
        }

        if (empty($errors = $validator->validate($body))) {
            return null;
        }

        return new ServerResponse(json_encode($errors), StatusCode::BAD_REQUEST);
    }
}