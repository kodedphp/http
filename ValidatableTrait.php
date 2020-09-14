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
use function Koded\Stdlib\json_serialize;

/**
 * @method Response|null getParsedBody
 */
trait ValidatableTrait
{
    public function validate(HttpInputValidator $validator): ?Response
    {
        $body = new Immutable($this->getParsedBody() ?? []);
        if (0 === $body->count()) {
            $errors = ['validate' => 'Nothing to validate', 'code' => StatusCode::BAD_REQUEST];
            return new ServerResponse(json_serialize($errors), StatusCode::BAD_REQUEST);
        }
        if (empty($errors = $validator->validate($body))) {
            return null;
        }
        $errors['code'] = (int)($errors['code'] ?? StatusCode::BAD_REQUEST);
        return new ServerResponse(json_serialize($errors), $errors['code']);
    }
}
