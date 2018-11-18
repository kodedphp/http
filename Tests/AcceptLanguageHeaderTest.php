<?php

namespace Koded\Http;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AcceptLanguageHeaderTest extends TestCase
{

    /**
     * @dataProvider dataForAsteriskSupport
     */
    public function test_match_with_asterisk($accept, $expect, $quality)
    {
        $negotiator = (new AcceptHeaderNegotiate('*'))->match($accept);

        $this->assertSame($expect, $negotiator->value(), 'Expects ' . $expect);
        $this->assertSame($quality, $negotiator->quality(), 'Expects q=' . $quality);
    }

    /**
     * @dataProvider dataForSupportedLanguages
     */
    public function test_with_preferred_languages($accept, $expect, $quality)
    {
        $negotiator = (new AcceptHeaderNegotiate('de,fr,en'))->match($accept);

        $this->assertSame($expect, $negotiator->value(), 'Expects ' . $expect);
        $this->assertSame($quality, $negotiator->quality(), 'Expects q=' . $quality);
    }

    public function test_invalid_accept_header()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('"" is not a valid Access header');
        (new AcceptHeaderNegotiate(''))->match('');
    }

    public function dataForAsteriskSupport()
    {
        return [
            ['*;q=0.5, fr;q=0.9, en;q=0.8, de;q=0.7', 'fr', 0.9],
            ['en-US,en;q=0.5', 'en-US', 1.0],
            ['*', '*', 1.0]
        ];
    }

    public function dataForSupportedLanguages()
    {
        return [
            ['fr;q=0.7, en;q=0.8, de;q=0.9, *;q=0.5', 'de', 0.9],
            ['en-US,en;q=0.5', 'en-US', 1.0],
            ['*', 'de', 1.0]
        ];
    }
}
