<?php

namespace Tests\Koded\Http\Client;

use Koded\Http\Client\ClientFactory;
use Koded\Http\Client\CurlClient;
use Koded\Http\Interfaces\HttpRequestClient;
use Koded\Http\ServerResponse;
use Koded\Http\StatusCode;
use PHPUnit\Framework\TestCase;
use Tests\Koded\Http\AssertionTestSupportTrait;

class CurlClientTest extends TestCase
{
    use ClientTestCaseTrait, AssertionTestSupportTrait;

    public function test_php_factory()
    {
        $options = $this->getObjectProperty($this->SUT, 'options');

        $this->assertArrayNotHasKey(CURLOPT_HTTPHEADER, $options, 'The header is not built yet');
        $this->assertArrayHasKey(CURLOPT_MAXREDIRS, $options);
        $this->assertArrayHasKey(CURLOPT_RETURNTRANSFER, $options);
        $this->assertArrayHasKey(CURLOPT_FOLLOWLOCATION, $options);
        $this->assertArrayHasKey(CURLOPT_SSL_VERIFYPEER, $options);
        $this->assertArrayHasKey(CURLOPT_SSL_VERIFYHOST, $options);
        $this->assertArrayHasKey(CURLOPT_USERAGENT, $options);
        $this->assertArrayHasKey(CURLOPT_FAILONERROR, $options);
        $this->assertArrayHasKey(CURLOPT_HTTP_VERSION, $options);
        $this->assertArrayHasKey(CURLOPT_TIMEOUT, $options);

        $this->assertSame(20, $options[CURLOPT_MAXREDIRS]);
        $this->assertSame(true, $options[CURLOPT_RETURNTRANSFER]);
        $this->assertSame(true, $options[CURLOPT_FOLLOWLOCATION]);
        $this->assertSame(1, $options[CURLOPT_SSL_VERIFYPEER]);
        $this->assertSame(2, $options[CURLOPT_SSL_VERIFYHOST]);
        $this->assertSame(HttpRequestClient::USER_AGENT, $options[CURLOPT_USERAGENT]);
        $this->assertSame(0, $options[CURLOPT_FAILONERROR]);
        $this->assertSame(CURL_HTTP_VERSION_1_1, $options[CURLOPT_HTTP_VERSION]);
        $this->assertSame(3.0, $options[CURLOPT_TIMEOUT]);
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
            ->verifySslHost(false)
            ->verifySslPeer(false);

        $options = $this->getObjectProperty($this->SUT, 'options');

        $this->assertSame('foo', $options[CURLOPT_USERAGENT]);
        $this->assertSame(5.0, $options[CURLOPT_TIMEOUT], 'Expects float (timeout)');
        $this->assertSame(2, $options[CURLOPT_MAXREDIRS]);
        $this->assertSame(false, $options[CURLOPT_FOLLOWLOCATION]);
        $this->assertSame(0, $options[CURLOPT_FAILONERROR]);
        $this->assertSame(0, $options[CURLOPT_SSL_VERIFYHOST]);
        $this->assertSame(0, $options[CURLOPT_SSL_VERIFYPEER]);
    }

    public function test_protocol_version()
    {
        $options = $this->getObjectProperty($this->SUT, 'options');
        $this->assertSame(CURL_HTTP_VERSION_1_1, $options[CURLOPT_HTTP_VERSION]);

        $this->SUT = $this->SUT->withProtocolVersion('1.0');
        $options = $this->getObjectProperty($this->SUT, 'options');
        $this->assertSame(CURL_HTTP_VERSION_1_0, $options[CURLOPT_HTTP_VERSION]);
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

    public function test_on_exception()
    {
        $SUT = new class('get', 'http://example.com') extends CurlClient
        {
            protected function createResource(): \CurlHandle|bool
            {
                throw new \Exception('Exception message');
            }
        };
        $response = $SUT->read();

        $this->assertSame($response->getHeaderLine('Content-type'), 'application/problem+json');
        $this->assertSame(StatusCode::INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertStringContainsString('Exception message', (string)$response->getBody());
    }

    /**
     * @group internet
     */
    protected function setUp(): void
    {
        if (false === extension_loaded('curl')) {
            $this->markTestSkipped('cURL extension is not installed on the testing environment');
        }

        $this->SUT = (new ClientFactory(ClientFactory::CURL))
            ->get('http://example.com')
            ->timeout(3);
    }
}
