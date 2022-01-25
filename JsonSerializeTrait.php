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

use function get_object_vars;

trait JsonSerializeTrait
{
    /**
     * Serialize the request object as JSON representation.
     *
     * @return mixed Request / Response object properties
     *               that can be serialized by json_encode()
     */
    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }
}
