<?php

namespace Tests\Koded\Http\Client;

use Koded\Http\Interfaces\{HttpRequestClient, HttpStatus};
use Koded\Http\Uri;
use function Koded\Http\create_stream;

/**
 * Trait ClientTestCaseTrait ensures some consistent behaviour
 * across the HTTP client implementations.
 *
 */

/**
 * @group internet
 */
trait ClientTestCaseTrait
{
    private ?HttpRequestClient $SUT;

    public function test_read_on_success()
    {
        $response = $this->SUT->read();

        $this->assertSame(HttpStatus::OK, $response->getStatusCode(), (string)$response->getBody());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertGreaterThan(0, (string)$response->getBody()->getSize());
    }

    public function test_should_exit_on_bad_url()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Please provide a valid URI');
        $this->expectExceptionCode(HttpStatus::BAD_REQUEST);

        $this->SUT->withUri(new Uri('scheme://host:port'));
    }

    public function test_should_exit_on_bad_request()
    {
        /** @var HttpRequestClient $SUT */
        $SUT = $this->SUT->withBody(create_stream(json_encode(['foo' => 'bar'])));

        $badResponse = $SUT->read();

        $this->assertSame(HttpStatus::BAD_REQUEST, $badResponse->getStatusCode(), get_class($SUT));
        $this->assertSame($badResponse->getHeaderLine('Content-type'), 'application/problem+json');
        $this->assertStringContainsString('failed to open stream: you should not set the message body with safe HTTP methods',
            (string)$badResponse->getBody());
    }

    protected function tearDown(): void
    {
        $this->SUT = null;
    }
}
