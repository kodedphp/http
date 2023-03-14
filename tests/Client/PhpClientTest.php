<?php

namespace Tests\Koded\Http\Client;

use Koded\Http\Client\ClientFactory;
use Koded\Http\Client\PhpClient;
use Koded\Http\Interfaces\ClientType;
use Koded\Http\Interfaces\HttpMethod;
use Koded\Http\Interfaces\HttpRequestClient;
use Koded\Http\Interfaces\HttpStatus;
use Koded\Http\ServerResponse;
use PHPUnit\Framework\TestCase;
use Tests\Koded\Http\AssertionTestSupportTrait;

class PhpClientTest extends TestCase
{
    use ClientTestCaseTrait, AssertionTestSupportTrait;

    public function test_php_factory()
    {
        $options = $this->getObjectProperty($this->SUT, 'options');

        $this->assertArrayNotHasKey('header', $options, 'Headers are not set up until read()');
        $this->assertArrayHasKey('protocol_version', $options);
        $this->assertArrayHasKey('user_agent', $options);
        $this->assertArrayHasKey('method', $options);
        $this->assertArrayHasKey('timeout', $options);
        $this->assertArrayHasKey('max_redirects', $options);
        $this->assertArrayHasKey('follow_location', $options);
        $this->assertArrayHasKey('ignore_errors', $options);

        $this->assertSame(1.1, $options['protocol_version']);
        $this->assertSame(HttpRequestClient::USER_AGENT, $options['user_agent']);
        $this->assertSame('GET', $options['method']);
        $this->assertSame(20, $options['max_redirects']);
        $this->assertSame(1, $options['follow_location']);
        $this->assertTrue($options['ignore_errors']);
        $this->assertFalse($options['ssl']['allow_self_signed']);
        $this->assertTrue($options['ssl']['verify_peer']);
        $this->assertSame(3.0, $options['timeout']);
        $this->assertSame('', (string)$this->SUT->getBody(), 'The body is empty');
    }

    public function test_setting_the_client_with_methods()
    {
        $this->SUT
            ->ignoreErrors(true)
            ->timeout(5)
            ->followLocation(false)
            ->maxRedirects(2)
            ->userAgent('foo')
            ->verifySslPeer(false)
            ->verifySslHost(true);

        $options = $this->getObjectProperty($this->SUT, 'options');

        $this->assertSame('foo', $options['user_agent']);
        $this->assertSame(5.0, $options['timeout']);
        $this->assertSame(2, $options['max_redirects']);
        $this->assertSame(0, $options['follow_location']);
        $this->assertSame(true, $options['ignore_errors']);
        $this->assertSame(true, $options['ssl']['allow_self_signed']);
        $this->assertSame(false, $options['ssl']['verify_peer']);
    }

    public function test_when_curl_returns_error()
    {
        $SUT = new class(HttpMethod::GET, 'http://example.com') extends PhpClient
        {
            protected function hasError($resource): bool
            {
                return true;
            }
        };
        $response = $SUT->read();

        $this->assertInstanceOf(ServerResponse::class, $response);
        $this->assertSame($response->getHeaderLine('Content-type'), 'application/problem+json');
        $this->assertSame(HttpStatus::FAILED_DEPENDENCY, $response->getStatusCode(),
            (string)$response->getBody());
    }

    public function test_when_creating_resource_fails()
    {
        $SUT = new class(HttpMethod::GET, 'http://example.com') extends PhpClient
        {
            protected function createResource($resource)
            {
                return false;
            }
        };
        $response = $SUT->read();

        $this->assertInstanceOf(ServerResponse::class, $response);
        $this->assertSame($response->getHeaderLine('Content-type'), 'application/problem+json');
        $this->assertSame(HttpStatus::FAILED_DEPENDENCY, $response->getStatusCode());
        $this->assertStringContainsString('The HTTP client is not created therefore cannot read anything',
            (string)$response->getBody());
    }

    public function test_on_exception()
    {
        $SUT = new class(HttpMethod::GET, 'http://example.com') extends PhpClient
        {
            protected function createResource($resource)
            {
                throw new \Exception('Exception message');
            }
        };
        $response = $SUT->read();

        $this->assertSame($response->getHeaderLine('Content-type'), 'application/problem+json');
        $this->assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertStringContainsString('Exception message', (string)$response->getBody());
    }

    protected function setUp(): void
    {
        $this->SUT = (new ClientFactory(ClientType::PHP))
            ->get('http://example.com')
            ->timeout(3);
    }
}
