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

use Koded\Http\Interfaces\{HttpInputValidator, HttpStatus, Response};
use Koded\Stdlib\{Data, Immutable};

/**
 * @method Response|null getParsedBody
 */
trait ValidatableTrait
{
    public function validate(HttpInputValidator $validator, Data &$input = null): ?Response
    {
        $input ??= new Immutable($this->getParsedBody() ?? []);
        if (0 === $input->count()) {
            $errors = ['validate' => 'Nothing to validate', 'code' => HttpStatus::BAD_REQUEST];
            return new JsonResponse($errors, HttpStatus::BAD_REQUEST, [
                'Content-Type' => 'application/problem+json'
            ]);
        }
        if (empty($errors = $validator->validate($input))) {
            return null;
        }
        $errors['status'] = (int)($errors['status'] ?? HttpStatus::BAD_REQUEST);
        return new JsonResponse($errors, $errors['status'], [
            'Content-Type' => 'application/problem+json'
        ]);
    }
}
