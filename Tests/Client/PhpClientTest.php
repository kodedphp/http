<?php

namespace Koded\Http\Client;

use Koded\Http\Interfaces\HttpRequestClient;
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
        $this->assertSame(false, $options['ignore_errors']);
        $this->assertSame(false, $options['ssl']['allow_self_signed']);
        $this->assertSame(false, $options['ssl']['verify_peer']);

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

    protected function setUp()
    {
        $this->SUT = (new ClientFactory(ClientFactory::PHP))->open('get', 'http://example.com');
    }
}
