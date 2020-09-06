<?php

namespace Koded\Http\Client;

use InvalidArgumentException;
use Koded\Http\Interfaces\Request;
use PHPUnit\Framework\TestCase;

/**
 * @group internet
 */
class ClientFactoryTest extends TestCase
{
    const URI = 'https://example.com';

    public function test_php_factory()
    {
        $instance = (new ClientFactory(ClientFactory::PHP))->get(self::URI);
        $this->assertInstanceOf(PhpClient::class, $instance);
    }

    public function test_curl_factory()
    {
        $instance = (new ClientFactory)->get(self::URI);
        $this->assertInstanceOf(CurlClient::class, $instance, 'CurlClient is the default');
    }

    public function test_factory_should_throw_exception_for_unknown_client()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('4 is not a valid HTTP client');
        (new ClientFactory(4))->get('localhost');
    }

    public function test_get()
    {
        $client = (new ClientFactory)->get(self::URI, []);
        $this->assertSame(Request::GET, $client->getMethod());
    }

    public function test_post()
    {
        $client = (new ClientFactory)->post(self::URI, []);
        $this->assertSame(Request::POST, $client->getMethod());
    }

    public function test_put()
    {
        $client = (new ClientFactory)->put(self::URI, []);
        $this->assertSame(Request::PUT, $client->getMethod());
    }

    public function test_head()
    {
        $client = (new ClientFactory)->head(self::URI, []);
        $this->assertSame(Request::HEAD, $client->getMethod());
    }

    public function test_patch()
    {
        $client = (new ClientFactory)->patch(self::URI, []);
        $this->assertSame(Request::PATCH, $client->getMethod());
    }

    public function test_delete()
    {
        $client = (new ClientFactory)->delete(self::URI, []);
        $this->assertSame(Request::DELETE, $client->getMethod());
    }

    public function test_psr18_client_create()
    {
        $client = (new ClientFactory)->client();
        $this->assertSame('HEAD', $client->getMethod());
        $this->assertEmpty((string)$client->getUri());
    }
}
