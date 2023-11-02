<?php

namespace Tests\Koded\Http;

use Koded\Http\ClientRequest;
use Koded\Http\Interfaces\HttpMethod;
use Koded\Http\Interfaces\HttpStatus;
use PHPUnit\Framework\TestCase;

class ClientRequestHeadersTest extends TestCase
{
    const URI = 'https://example.org';

    public function test_should_set_the_associative_header_array()
    {
        $request = new ClientRequest(HttpMethod::POST, self::URI, null, [
            'Authorization'       => 'Bearer 1234567890',
            'X_CUSTOM_CRAP'       => 'Hello',
            'Other-Creative-Junk' => 'Useless value'
        ]);

        $this->assertSame(['Bearer 1234567890'], $request->getHeader('authOriZAtioN'));
        $this->assertSame(['Hello'], $request->getHeader('X-Custom-Crap'));
        $this->assertSame(['Useless value'], $request->getHeader('other-creative-junk'));
    }

    public function test_should_throw_exception_for_invalid_header_array()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(HttpStatus::BAD_REQUEST);
        $this->expectExceptionMessage('must be of type string, int given');

        new ClientRequest(HttpMethod::POST, self::URI, null, [
            'Authorization: Bearer 1234567890',
            'X_CUSTOM_CRAP: Hello'
        ]);
    }
}
