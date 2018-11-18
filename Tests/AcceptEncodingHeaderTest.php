<?php

namespace Koded\Http;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AcceptEncodingHeaderTest extends TestCase
{

    public function test_empty_header()
    {
        $this->expectException(InvalidArgumentException::class);
        (new AcceptHeaderNegotiate('*'))->match('');
    }

    /**
     * @dataProvider dataWithAsterisk
     */
    public function test_with_asterisk($accept, $expect, $quality)
    {
        $encoding = (new AcceptHeaderNegotiate('*'))->match($accept);

        $this->assertSame($expect, $encoding->value(), 'Expects ' . $expect);
        $this->assertSame($quality, $encoding->quality(), 'Expects q=' . $quality);
    }

    /**
     * @dataProvider dataWithSupportedEncoding
     */
    public function test_with_preferred_encoding($accept, $expect, $quality)
    {
        $negotiator = (new AcceptHeaderNegotiate('gzip, compress, deflate'))->match($accept);

        $this->assertSame($expect, $negotiator->value(), 'Expects ' . $expect);
        $this->assertSame($quality, $negotiator->quality(), 'Expects q=' . $quality);
    }

    public function test_invalid_accept_header()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('"" is not a valid Access header');
        (new AcceptHeaderNegotiate('*'))->match('');
    }

    public function dataWithAsterisk()
    {
        return [
            ['br;q=1.0, gzip;q=0.8, *;q=0.1', 'br', 1.0],
            ['deflate', 'deflate', 1.0],
            ['compress, gzip', 'compress', 1.0],
            ['*', '*', 1.0],
            ['compress;q=0.5, gzip;q=0.3', 'compress', 0.5],
            ['gzip;q=1.0, identity; q=0.5, *;q=0', 'gzip', 1.0],
        ];
    }

    public function dataWithSupportedEncoding()
    {
        return [
            ['br;q=1.0, gzip;q=0.8, *;q=0.1', 'gzip', 0.8],
            ['deflate', 'deflate', 1.0],
            ['compress, gzip', 'compress', 1.0],
            ['*', 'gzip', 1.0],
            ['compress;q=0.5, gzip;q=0.3', 'compress', 0.5],
            ['gzip;q=0.5;var=1, identity; q=0.5, *;q=0', 'gzip', 0.5],
        ];
    }
}
