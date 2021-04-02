<?php

namespace Tests\Koded\Http;

use Psr\Http\Message\StreamInterface;
use function Koded\Http\create_stream;

class StreamIntegrationTest extends \Http\Psr7Test\StreamIntegrationTest
{
    /**
     * @param string|resource|StreamInterface $data
     *
     * @return StreamInterface
     */
    public function createStream($data)
    {
        return create_stream($data);
    }
}
