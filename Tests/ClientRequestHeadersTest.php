<?php

namespace Koded\Http;

use PHPUnit\Framework\TestCase;

class ClientRequestHeadersTest extends TestCase
{

    const URI = 'https://example.org';

    public function test_should_set_the_associative_header_array()
    {
        $request = new ClientRequest('post', self::URI, null, [
            'Authorization'       => 'Bearer 1234567890',
            'X_CUSTOM_CRAP'       => 'Hello',
            'Other-Creative-Junk' => 'Useless value'
        ]);

        $this->assertSame(['Bearer 1234567890'], $request->getHeader('authOriZAtioN'));
        $this->assertSame(['Hello'], $request->getHeader('X-Custom-Crap'));
        $this->assertSame(['Useless value'], $request->getHeader('other-creative-junk'));
    }

    public function test_should_ignore_the_bloated_header_array()
    {
        $request = new ClientRequest('post', self::URI, null, [
            'Authorization: Bearer 1234567890',
            'X_CUSTOM_CRAP: Hello'
        ]);

        $this->assertFalse($request->hasHeader('Authorization'));
        $this->assertFalse($request->hasHeader('x-custom-crap'));
    }
}
