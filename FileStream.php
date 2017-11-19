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

class FileStream extends Stream {

    public function __construct(string $filename, string $mode = 'r') {
        parent::__construct(fopen($filename, $mode));
    }

    public function getContents(): string {
        return stream_to_string($this);
    }
}
