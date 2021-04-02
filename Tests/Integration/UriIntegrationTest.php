<?php

namespace Tests\Koded\Http;

use Koded\Http\Uri;
use Psr\Http\Message\UriInterface;

class UriIntegrationTest extends \Http\Psr7Test\UriIntegrationTest
{
    /**
     * @param string $uri
     *
     * @return UriInterface
     */
    public function createUri($uri)
    {
        unset($_SERVER['HTTP_HOST']);
        return new Uri($uri);
    }
}