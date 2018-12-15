<?php

namespace Koded\Http\Client;

use Exception;
use Koded\Http\Interfaces\HttpRequestClient;
use Koded\Http\StatusCode;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PhpClientTest extends TestCase
{

    use ClientTestCaseTrait;

    public function test_php_factory()
    {
        $options = $this->getOptions();

        $this->assertNotEmpty($options['header']);
        $this->assertArraySubset(['Host:example.com'], $options['header']);

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
        $this->assertFalse($options['ssl']['verify_peer']);
        $this->assertSame('', (string)$this->SUT->getBody(), 'The body is empty');
        $this->assertSame(3.0, $options['timeout']);

    }

    public function test_methods()
    {
        $this->SUT
            ->ignoreErrors(true)
            ->timeout(5)
            ->followLocation(false)
            ->maxRedirects(2)
            ->userAgent('foo')
            ->verifySslPeer(true)
            ->verifySslHost(true);

        $options = $this->getOptions();

        $this->assertSame('foo', $options['user_agent']);
        $this->assertSame(5.0, $options['timeout']);
        $this->assertSame(2, $options['max_redirects']);
        $this->assertSame(0, $options['follow_location']);
        $this->assertSame(true, $options['ignore_errors']);
        $this->assertSame(true, $options['ssl']['allow_self_signed']);
        $this->assertSame(true, $options['ssl']['verify_peer']);
    }

    /**
     * @group internet
     */
    public function test_internal_server_exception()
    {
        $SUT = new class('get', 'http://example.org') extends PhpClient
        {
            protected function createResource($context): bool
            {
                throw new Exception;
            }
        };

        $response = $SUT->read();

        $this->assertSame(StatusCode::INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    /**
     * @group internet
     */
    public function test_when_creating_stream_fails()
    {
        $SUT = new class('get', 'http://example.org') extends PhpClient
        {
            protected function createResource($context): bool
            {
                return false;
            }
        };

        $response = $SUT->read();

        $this->assertSame(StatusCode::FAILED_DEPENDENCY, $response->getStatusCode(), (string)$response->getBody());
    }

    /**
     * @group internet
     */
    public function test_body_prepare()
    {
        $SUT = new PhpClient('post', 'http://example.org', ['foo' => 'bar']);

        $proto   = new ReflectionClass($SUT);
        $options = $proto->getProperty('options');
        $options->setAccessible(true);

        $this->assertSame('foo=bar', $options->getValue($SUT)['content']);
    }

    protected function setUp()
    {
        $this->SUT = (new ClientFactory(ClientFactory::PHP))
            ->get('http://example.com')
            ->timeout(3);
    }
}
