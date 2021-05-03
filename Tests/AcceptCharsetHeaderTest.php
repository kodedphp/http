<?php

namespace Tests\Koded\Http;

use Koded\Http\AcceptHeaderNegotiator;
use PHPUnit\Framework\TestCase;

class AcceptCharsetHeaderTest extends TestCase
{

    public function test_without_q()
    {
        $charset = (new AcceptHeaderNegotiator('*'))->match('utf-8, iso-8859-1;q=0.5, *;q=0.1');

        $this->assertSame('utf-8', $charset->value(), 'Expects utf-8');
        $this->assertSame(1.0, $charset->quality(), 'Expects q=1.0');
    }

    public function test_quality()
    {
        $charset = (new AcceptHeaderNegotiator('*'))->match('iso-8859-5;q=0.2, unicode-1-1;q=0.8');

        $this->assertSame('unicode-1-1', $charset->value(), 'Expects unicode-1-1');
        $this->assertSame(0.8, $charset->quality(), 'Expects q=0.8');
    }

    public function test_empty_accept_header()
    {
        $match = (new AcceptHeaderNegotiator(''))->match('*');
        $this->assertEquals('', $match->value(), 'Returns empty value with undefined support headers');
    }

    /**
     * @dataProvider dataForPreferredCharset
     */
    public function test_with_preferred_charset($accept, $expect, $quality)
    {
        $charset = (new AcceptHeaderNegotiator('utf-8, iso-8859-1;q=0.5, *;q=0.1'))->match($accept);

        $this->assertSame($expect, $charset->value(), 'Expects ' . $expect);
        $this->assertSame($quality, $charset->quality(), 'Expects q=' . $quality);
    }

    public function dataForPreferredCharset()
    {
        return [
            ['utf-8, iso-8859-1;q=0.5, *;q=0.1', 'utf-8', 1.0],
            ['utf-8;q=0.8, */*', 'utf-8', 0.8],
            ['iso-8859-1', 'iso-8859-1', 0.5],
            ['utf-16', 'utf-16', 0.1],
            ['utf-16, iso-8859-1;q=0.7', 'iso-8859-1', 0.7],
        ];
    }
}
