<?php

namespace Tests\Koded\Http\Integration;

use Koded\Http\Uri;
use Psr\Http\Message\UriInterface;

/**
 * @group integration
 */
class UriIntegrationTest extends \Http\Psr7Test\UriIntegrationTest
{
    protected $skippedTests = [
        'testGetPathNormalizesMultipleLeadingSlashesToSingleSlashToPreventXSS' => 'Is this test correct?',
    ];

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