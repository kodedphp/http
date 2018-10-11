<?php

namespace Koded\Http;

use PHPUnit\Framework\TestCase;

class StatusCodeTest extends TestCase
{

    public function test__callStatic()
    {
        $this->assertSame('405 Method Not Allowed', StatusCode::METHOD_NOT_ALLOWED(true));
        $this->assertSame('Method Not Allowed', StatusCode::METHOD_NOT_ALLOWED(false));
        $this->assertSame(null, StatusCode::something_non_existent());
    }

    public function test_description()
    {
        $this->assertSame('', StatusCode::description(StatusCode::CREATED));
        $this->assertSame('The origin server requires the request to be conditional',
            StatusCode::description(StatusCode::PRECONDITION_REQUIRED));
    }
}
