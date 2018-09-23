<?php

namespace Koded\Http\Client;

use Koded\Http\StatusCode;
use Koded\Http\Interfaces\HttpRequestClient;
use Koded\Http\ServerResponse;
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
        $this->assertSame(false, $options[CURLOPT_SSL_VERIFYPEER]);
        $this->assertSame(false, $options[CURLOPT_SSL_VERIFYHOST]);
        $this->assertSame(HttpRequestClient::USER_AGENT, $options[CURLOPT_USERAGENT]);
        $this->assertSame(0, $options[CURLOPT_FAILONERROR]);
        $this->assertSame('1.1', $options[CURLOPT_HTTP_VERSION]);
        $this->assertSame(60.0, $options[CURLOPT_TIMEOUT]);

        $this->assertSame('', (string)$this->SUT->getBody(), 'The body is empty');
    }

    public function test_methods()
    {
        $this->SUT
            ->ignoreErrors(true)
            ->timeout(5.0)
            ->followLocation(false)
            ->maxRedirects(2)
            ->userAgent('foo')
            ->verifySslHost(true)
            ->verifySslPeer(true);

        $options = $this->getOptions();

        $this->assertSame('foo', $options[CURLOPT_USERAGENT]);
        $this->assertSame(5.0, $options[CURLOPT_TIMEOUT]);
        $this->assertSame(2, $options[CURLOPT_MAXREDIRS]);
        $this->assertSame(false, $options[CURLOPT_FOLLOWLOCATION]);
        $this->assertSame(0, $options[CURLOPT_FAILONERROR]);
        $this->assertTrue($options[CURLOPT_SSL_VERIFYHOST]);
        $this->assertTrue($options[CURLOPT_SSL_VERIFYPEER]);
    }

    public function test_bad_request_response()
    {
        $client   = new \ReflectionClass($this->SUT);
        $resource = $client->getProperty('resource');
        $resource->setAccessible(true);
        $resource->setValue($this->SUT, false);

        $response = $this->SUT->read();
        $this->assertSame(StatusCode::PRECONDITION_FAILED, $response->getStatusCode());
        $this->assertSame('The HTTP client is not created therefore cannot read anything', (string)$response->getBody());
    }

    public function test_internal_server_exception()
    {
        $client   = new \ReflectionClass($this->SUT);
        $resource = $client->getProperty('resource');
        $resource->setAccessible(true);
        $resource->setValue($this->SUT, new \stdClass); // this throws exception from curl_exec()

        $response = $this->SUT->read();

        $this->assertInstanceOf(ServerResponse::class, $response);
        $this->assertSame(StatusCode::INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertSame('curl_setopt_array() expects parameter 1 to be resource, object given',
            (string)$response->getBody());
    }

    public function test_when_curl_returns_error()
    {
        $SUT = new class('get', 'http://kodeart.com') extends CurlClient
        {
            protected function hasError(): bool
            {
                return true;
            }
        };

        $response = $SUT->read();

        $this->assertInstanceOf(ServerResponse::class, $response);
        $this->assertSame(StatusCode::UNPROCESSABLE_ENTITY, $response->getStatusCode(), (string)$response->getBody());
    }

    protected function setUp()
    {
        if (false === function_exists('curl_init')) {
            $this->markTestSkipped('cURL extension is not installed on the testing environment');
        }

        $this->SUT = (new ClientFactory(ClientFactory::CURL))->get('http://example.com');
    }
}
