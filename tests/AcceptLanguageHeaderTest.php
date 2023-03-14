<?php

namespace Tests\Koded\Http;

use Koded\Http\AcceptHeaderNegotiator;
use PHPUnit\Framework\TestCase;

class AcceptLanguageHeaderTest extends TestCase
{
    /**
     * @dataProvider dataForSupportedLanguagesWithAsterisk
     */
    public function test_match_with_asterisk($accept, $expect, $quality)
    {
        $negotiator = (new AcceptHeaderNegotiator('*'))->match($accept);

        $this->assertSame($expect, $negotiator->value(), 'Expects ' . $expect);
        $this->assertSame($quality, $negotiator->quality(), 'Expects q=' . $quality);
    }

    /**
     * @dataProvider dataForSupportedLanguages
     */
    public function test_with_preferred_languages($accept, $expect, $quality)
    {
        $negotiator = (new AcceptHeaderNegotiator('de,fr,en'))->match($accept);

        $this->assertSame($expect, $negotiator->value(), 'Expects ' . $expect);
        $this->assertSame($quality, $negotiator->quality(), 'Expects q=' . $quality);
    }

    public function test_empty_accept_and_support_headers()
    {
        $match = (new AcceptHeaderNegotiator(''))->match('');
        $this->assertEquals('', $match->value(), 'Empty headers returns empty matched value');
    }

    public function dataForSupportedLanguagesWithAsterisk()
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
            ['en-US,en;q=0.5', 'en', 0.5],
            ['*', 'de', 1.0]
        ];
    }
}
