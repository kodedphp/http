<?php

namespace Koded\Http;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AcceptHeaderNegotiateTest extends TestCase
{
    /**
     * @dataProvider dataMediaTypes
     *
     * @see          The examples at https://tools.ietf.org/html/rfc7231#section-5.3.2
     */
    public function test_media_type_and_quality_match($accept, $expects, $quality)
    {
        $supported = 'text/*;q=0.3, text/html;q=0.7, text/html;level=1, text/html;level=2;q=0.4, */*;q=0.5';
        $match     = (new AcceptHeaderNegotiator($supported))->match($accept);

        $this->assertSame($expects, $match->value(), 'Expects mimetype ' . $expects);
        $this->assertSame($expects, (string)$match);
        $this->assertSame($quality, $match->quality(), 'Expects q=' . $quality);
    }

    public function test_catch_all_supported_header()
    {
        $match = (new AcceptHeaderNegotiator('*/*;q=0.8'))->match('text/plain;q=0.2');
        $this->assertEquals('text/plain', $match->value());
        $this->assertEquals(0.2, $match->quality(), 'Gets the "q" from Accept header');
        $this->assertEquals(0.2, $match->weight());
    }

    public function test_catch_all_accept_header()
    {
        $match = (new AcceptHeaderNegotiator('text/plain;q=0.8'))->match('*/*;q=0.2');
        $this->assertEquals('text/plain', $match->value());
        $this->assertEquals(0.2, $match->quality(), 'Gets the "q" from accept header');
        $this->assertEquals(0, $match->weight());
    }

    public function test_when_media_type_does_not_match()
    {
        $match = (new AcceptHeaderNegotiator('application/json'))->match('image/jpeg');

        $this->assertSame('', $match->value(),
            'Expects EMPTY value, because the Accept header did not match anything');

        $this->assertSame(0.0, $match->quality(), 'Expects q=0, because the Accept header did not match anything');
    }

    public function test_spec_wrong_asterisks_for_mediatype_and_q()
    {
        $match = (new AcceptHeaderNegotiator('application/json'))->match('*;*');
        $this->assertSame('application/json', $match->value());
        $this->assertSame(1.0, $match->quality());
    }

    public function test_with_asterisk_support_header()
    {
        $match = (new AcceptHeaderNegotiator('application/json'))->match('*');
        $this->assertSame('application/json', $match->value());
        $this->assertSame(1.0, $match->quality());
    }

    public function test_rfc7231_spec_for_invalid_access_header_type()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('"*/json" is not a valid Access header');
        (new AcceptHeaderNegotiator('*/json'))->match('*');
    }

    public function test_wrong_media_type()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('"&^%$" is not a valid Access header');
        (new AcceptHeaderNegotiator('&^%$'))->match('*');
    }

    public function test_useful_media_type_with_weird_types()
    {
        $accept = new AcceptHeaderNegotiator('application/json;q=0.4, */*;q=0.7');
        $match  = $accept->match('application/vnd.api-v1+json');
        $this->assertSame('application/json', $match->value(), 'Normal media type is expected');
        $this->assertSame(0.4, $match->quality());
    }

    public function test_weird_media_type_with_useful_type()
    {
        $accept = new AcceptHeaderNegotiator('application/vnd.api-v1+json');

        $match = $accept->match('application/json');
        $this->assertSame('application/json', $match->value(), 'Normal media type is expected');
        $this->assertSame(1.0, $match->quality());

        $match = $accept->match('application/*');
        $this->assertSame('application/json', $match->value(), 'Normal media type is expected');
        $this->assertSame(1.0, $match->quality());
    }

    public function test_weird_media_types_with_weird_type()
    {
        $accept = new AcceptHeaderNegotiator('application/json;q=0.4, */*;q=0.7');
        $match  = $accept->match('application/vnd.api-v1+json');
        $this->assertSame('application/json', $match->value(), 'Normal media type is expected');
        $this->assertSame(0.4, $match->quality());
    }

    public function test_obscure_media_types_when_both_are_different()
    {
        $accept = new AcceptHeaderNegotiator('application/vnd.api+json');
        $match  = $accept->match('application/xhtml+xml');
        $this->assertSame('', $match->value(), 'No match');
        $this->assertSame(0.0, $match->quality());
    }

    public function test_denied_supported_header()
    {
        $accept = new AcceptHeaderNegotiator('text/html;q=0');
        $match  = $accept->match('text/html');
        $this->assertSame('', $match->value());
        $this->assertSame(0.0, $match->quality());
    }

    public function test_denied_accept_header()
    {
        $accept = new AcceptHeaderNegotiator('text/html');
        $match  = $accept->match('text/html;q=0');
        $this->assertSame('', $match->value());
        $this->assertSame(0.0, $match->quality());
    }

    /**
     * @dataProvider dataObscureTypes
     */
    public function test_obscure_media_types($accept, $expects, $quality)
    {
        $match = (new AcceptHeaderNegotiator('application/vnd.api+json'))->match($accept);
        $this->assertSame($expects, $match->value(), 'Expects mimetype ' . $expects);
        $this->assertSame($quality, $match->quality(), 'Expects q=' . $quality);
    }

    /**
     * Test weight.
     *
     * @dataProvider dataPrecedence
     */
    public function test_media_type_order_of_precedence($accept, $expected, $precedence)
    {
        $supports = 'text/*, text/plain, text/plain;format=flowed, */*;q=0.3';
        $header   = (new AcceptHeaderNegotiator($supports))->match($accept);

        $this->assertSame($expected, $header->value());
        $this->assertSame($precedence, $header->weight());
    }

    // 'text/*;q=0.3, text/html;q=0.7, text/html;level=1, text/html;level=2;q=0.4, */*;q=0.5'
    public function dataMediaTypes()
    {
        return [
            ['text/html;level=1', 'text/html', 1.0],
            ['text/html', 'text/html', 0.7],
            ['text/plain', 'text/plain', 0.3],
            ['image/jpeg', 'image/jpeg', 0.5],
            ['text/html;level=2', 'text/html', 0.4],
            ['text/html;level=3', 'text/html', 0.7],
        ];
    }

    public function dataObscureTypes()
    {
        return [
            ['application/json', 'application/json', 1.0],
            ['application/vnd.api+json;level=2', 'application/json', 1.0],
            ['text/*;q=0.5', '', 0.0],
            ['text/*;q=0.5, */*; q=0.1', 'application/json', 0.1],
            ['*/*; q=0.1', 'application/json', 0.1],
        ];
    }

    // text/*, text/plain, text/plain;format=flowed, */*;q=0.3
    public function dataPrecedence()
    {
        return [
            ['text/plain;format=flowed', 'text/plain', 103.0],
            ['text/plain', 'text/plain', 102.0],
            ['text/xml', 'text/xml', 102.0],
            ['any/thing', 'any/thing', 0.3],
        ];
    }
}
