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

trait JsonSerializeTrait
{

    /**
     * Serialize the request object as JSON representation.
     *
     * @return array Request object properties (not a JSON serialized request object)
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
