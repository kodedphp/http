<?php

namespace Tests\Koded\Http\Client;

use Koded\Http\Client\ClientFactory;
use Koded\Http\Interfaces\HttpRequestClient;
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
