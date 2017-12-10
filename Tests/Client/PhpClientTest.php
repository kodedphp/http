<?php

namespace Koded\Http\Client;

use Exception;
use Koded\Http\HttpStatus;
use Koded\Http\Interfaces\HttpRequestClient;
use Koded\Http\ServerResponse;
use PHPUnit\Framework\TestCase;

class PhpClientTest extends TestCase
{

    use ClientTestCaseTrait;

    public function test_php_factory()
    {
        $options = $this->getOptions();

        $this->assertArrayNotHasKey('header', $options, 'The header is not built yet');
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
        $this->assertSame(60.0, $options['timeout']);
        $this->assertSame(20, $options['max_redirects']);
        $this->assertSame(1, $options['follow_location']);
        $this->assertTrue($options['ignore_errors']);
        $this->assertFalse($options['ssl']['allow_self_signed']);
        $this->assertFalse($options['ssl']['verify_peer']);

        $this->assertSame('', (string)$this->SUT->getBody(), 'The body is empty');
    }

    public function test_methods()
    {
        $this->SUT
            ->setIgnoreErrors(true)
            ->setTimeout(5)
            ->setFollowLocation(false)
            ->setMaxRedirects(2)
            ->setUserAgent('foo')
            ->setVerifySslPeer(true)
            ->setVerifySslHost(true);

        $options = $this->getOptions();

        $this->assertSame('foo', $options['user_agent']);
        $this->assertSame(5.0, $options['timeout']);
        $this->assertSame(2, $options['max_redirects']);
        $this->assertSame(0, $options['follow_location']);
        $this->assertSame(true, $options['ignore_errors']);
        $this->assertSame(true, $options['ssl']['allow_self_signed']);
        $this->assertSame(true, $options['ssl']['verify_peer']);
    }

    public function test_internal_server_exception()
    {
        $SUT = new class('get', 'http://example.org') extends PhpClient
        {
            protected function createResource($context): bool
            {
                throw new Exception;
            }
        };

        $SUT->open();
        $response = $SUT->read();

        $this->assertInstanceOf(ServerResponse::class, $response);
        $this->assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    public function test_when_creating_stream_fails()
    {
        $SUT = new class('get', 'http://example.org') extends PhpClient
        {
            protected function createResource($context): bool
            {
                return false;
            }
        };

        $SUT->open();
        $response = $SUT->read();

        $this->assertInstanceOf(ServerResponse::class, $response);
        $this->assertSame(HttpStatus::UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    protected function setUp()
    {
        $this->SUT = (new ClientFactory(ClientFactory::PHP))->open('get', 'http://example.com');
    }
}
