<?php

namespace Koded\Http\Client;

use Exception;
use Koded\Http\{Interfaces\HttpRequestClient, ServerResponse, StatusCode};
use PHPUnit\Framework\TestCase;

class CurlClientTest extends TestCase
{
    use ClientTestCaseTrait;

    public function test_php_factory()
    {
        $options = $this->getOptions();

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

    public function test_methods()
    {
        $this->SUT
            ->ignoreErrors(true)
            ->timeout(5)
            ->followLocation(false)
            ->maxRedirects(2)
            ->userAgent('foo')
            ->verifySslHost(false)
            ->verifySslPeer(false);

        $options = $this->getOptions();

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
        $options = $this->getOptions();
        $this->assertSame(CURL_HTTP_VERSION_1_1, $options[CURLOPT_HTTP_VERSION]);

        $this->SUT = $this->SUT->withProtocolVersion('1.0');
        $options   = $this->getOptions();
        $this->assertSame(CURL_HTTP_VERSION_1_0, $options[CURLOPT_HTTP_VERSION]);
    }

    /**
     * @group internet
     */
    public function test_bad_request_response()
    {
        $SUT = new class('get', 'http://example.com') extends CurlClient
        {
            protected function createResource()
            {
                return false;
            }
        };

        $response = $SUT->read();

        $this->assertInstanceOf(ServerResponse::class, $response);
        $this->assertSame(StatusCode::PRECONDITION_FAILED, $response->getStatusCode());
        $this->assertSame('The HTTP client is not created therefore cannot read anything',
            (string)$response->getBody());
    }

    /**
     * @group internet
     */
    public function test_internal_server_exception()
    {
        $SUT = new class('get', 'http://example.com') extends CurlClient
        {
            protected function createResource()
            {
                return new \stdClass();
            }
        };

        $response = $SUT->read();

        $this->assertInstanceOf(ServerResponse::class, $response);
        $this->assertSame(StatusCode::INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertSame('curl_setopt_array() expects parameter 1 to be resource, object given',
            (string)$response->getBody());
    }

    public function test_internal_server_error_by_throwing_exception()
    {
        $SUT = new class('get', 'http://example.com') extends CurlClient
        {
            protected function createResource()
            {
                throw new Exception('Exception message');
            }
        };

        $response = $SUT->read();

        $this->assertInstanceOf(ServerResponse::class, $response);
        $this->assertSame(StatusCode::INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertSame('Exception message', (string)$response->getBody());
    }

    /**
     * @group internet
     */
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
        $this->assertSame(StatusCode::FAILED_DEPENDENCY, $response->getStatusCode(), (string)$response->getBody());
    }

    /**
     * @group internet
     */
    protected function setUp()
    {
        if (false === extension_loaded('curl')) {
            $this->markTestSkipped('cURL extension is not installed on the testing environment');
        }

        $this->SUT = (new ClientFactory(ClientFactory::CURL))
            ->get('http://example.com')
            ->timeout(3);
    }
}
