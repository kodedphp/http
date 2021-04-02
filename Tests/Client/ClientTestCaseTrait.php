<?php

namespace Tests\Koded\Http\Client;

use Koded\Http\{Client\CurlClient, Interfaces\HttpRequestClient, ServerResponse, StatusCode, Uri};
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

        $this->assertSame(StatusCode::OK, $response->getStatusCode(), (string)$response->getBody());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertGreaterThan(0, (string)$response->getBody()->getSize());
    }

    public function test_should_exit_on_bad_url()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Please provide a valid URI');
        $this->expectExceptionCode(StatusCode::BAD_REQUEST);

        $this->SUT->withUri(new Uri('scheme://host:port'));
    }

    public function test_should_exit_on_bad_request()
    {
        /** @var HttpRequestClient $SUT */
        $SUT = $this->SUT->withBody(create_stream(json_encode(['foo' => 'bar'])));

        $badResponse = $SUT->read();

        $this->assertSame(StatusCode::BAD_REQUEST, $badResponse->getStatusCode(), get_class($SUT));
        $this->assertSame($badResponse->getHeaderLine('Content-type'), 'application/problem+json');
        $this->assertStringContainsString('failed to open stream: you should not set the message body with safe HTTP methods',
            (string)$badResponse->getBody());
    }

    public function test_when_curl_returns_error()
    {
        $SUT = new class('get', 'http://example.com') extends CurlClient
        {
            protected function hasError($resource): bool
            {
                return true;
            }
        };

        $response = $SUT->read();

        $this->assertInstanceOf(ServerResponse::class, $response);
        $this->assertSame($response->getHeaderLine('Content-type'), 'application/problem+json');
        $this->assertSame(StatusCode::FAILED_DEPENDENCY, $response->getStatusCode(),
            (string)$response->getBody());
    }

    public function test_when_creating_resource_fails()
    {
        $SUT = new class('get', 'http://example.com') extends CurlClient
        {
            protected function createResource(): \CurlHandle|bool
            {
                return false;
            }
        };

        $response = $SUT->read();

        $this->assertInstanceOf(ServerResponse::class, $response);
        $this->assertSame($response->getHeaderLine('Content-type'), 'application/problem+json');
        $this->assertSame(StatusCode::FAILED_DEPENDENCY, $response->getStatusCode());
        $this->assertStringContainsString('The HTTP client is not created therefore cannot read anything',
            (string)$response->getBody());
    }

    protected function tearDown(): void
    {
        $this->SUT = null;
    }
}
