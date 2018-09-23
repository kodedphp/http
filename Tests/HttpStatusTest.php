<?php

namespace Koded\Tests\Http;

use Koded\Http\StatusCode;
use PHPUnit\Framework\TestCase;

class HttpStatusTest extends TestCase
{

    /**
     * @test
     */
    public function it_should_return_status_string_without_code()
    {
        $this->assertSame('Multiple Choices', StatusCode::MULTIPLE_CHOICES());
    }

    /**
     * @test
     */
    public function it_should_return_status_string_with_code()
    {
        $this->assertSame('401 Unauthorized', StatusCode::UNAUTHORIZED(true));
    }
}
