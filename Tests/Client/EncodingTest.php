<?php

namespace Tests\Koded\Http\Client;

use Koded\Http\{Client\CurlClient,
    Client\PhpClient,
    Interfaces\HttpMethod,
    Interfaces\HttpRequestClient,
    Interfaces\HttpStatus};
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\{ClientExceptionInterface, ClientInterface};


class EncodingTest extends TestCase
{
    /**
     * @dataProvider clients
     */
    public function test_default_encoding(HttpRequestClient $client)
    {
        $encoding = $this->getProperty($client, 'encoding');
        $this->assertSame(PHP_QUERY_RFC3986, $encoding, 'Client ' . get_class($client));
    }

    /**
     * @dataProvider clients
     */
    public function test_encoding_setter_and_object_headers(HttpRequestClient $client)
    {
        $client->withEncoding(PHP_QUERY_RFC1738);

        $encoding = $this->getProperty($client, 'encoding');

        $this->assertSame(PHP_QUERY_RFC1738, $encoding);
        $this->assertArrayNotHasKey('Content-Type', $client->getHeaders(),
            'The Content-type header is not set until read() is called');
    }

    /**
     * @dataProvider clients
     */
    public function test_non_supported_encoding_types(HttpRequestClient $client)
    {
        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionCode(HttpStatus::BAD_REQUEST);
        $this->expectExceptionMessage('Invalid encoding type');

        $client->withEncoding(-1);
    }

    /**
     * @group        internet
     * @dataProvider clients
     */
    public function test_rfc1738_encoding(HttpRequestClient $client)
    {
        $name = get_class($client);
        $client->withEncoding(PHP_QUERY_RFC1738);
        $client->read();

        $this->assertSame(HttpRequestClient::X_WWW_FORM_URLENCODED, $client->getHeaderLine('Content-Type'),
            'Content-Type is set to "application/x-www-form-urlencoded"; Client ' . $name);

        $expected = 'foo=bar+qux+zim&email=johndoe%40example.com&misc=100%25%260%25%7C%3F%23';

        $this->assertSame($expected, $client->getBody()->getContents(),
            'Client body is re-written and encoded as per RFC-1738; Client: ' . $name);
    }

    /**
     * @group internet
     * @dataProvider clients
     */
    public function test_rfc3986_encoding(HttpRequestClient $client)
    {
        $name = get_class($client);
        $client->withEncoding(PHP_QUERY_RFC3986);
        $client->read();

        $this->assertSame(HttpRequestClient::X_WWW_FORM_URLENCODED, $client->getHeaderLine('Content-Type'),
            'Content-Type is set to "application/x-www-form-urlencoded"; Client ' . $name);

        $expected = 'foo=bar%20qux%20zim&email=johndoe%40example.com&misc=100%25%260%25%7C%3F%23';

        $this->assertSame($expected, $client->getBody()->getContents(),
            'Client body is re-written and encoded as per RFC-3986; Client ' . $name);
    }

    /**
     * @group        internet
     * @dataProvider clients
     */
    public function test_with_no_encoding(HttpRequestClient $client)
    {
        $name = get_class($client);
        $client->withEncoding(0);
        $client->read();

        $this->assertSame('', $client->getHeaderLine('Content-Type'),
            'Content-Type is NOT set; Client ' . $name);

        $expected = '{"foo":"bar qux zim","email":"johndoe@example.com","misc":"100%&0%|?#"}';

        $this->assertSame($expected, $client->getBody()->getContents(),
            'Client body is encoded as JSON by default; Client ' . $name);
    }

    public function clients()
    {
        $args = [
//            'POST',
            HttpMethod::POST,
            'https://example.com/',
            [
                'foo'   => 'bar qux zim',
                'email' => 'johndoe@example.com',
                'misc'  => '100%&0%|?#'
            ]
        ];

        return [
            [new CurlClient(...$args)],
            [new PhpClient(...$args)],
        ];
    }

    protected function setUp(): void
    {
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
    }

    private function getProperty(ClientInterface $client, string $property)
    {
        $proto   = new \ReflectionClass($client);
        $options = $proto->getProperty($property);
        $options->setAccessible(true);

        return $options->getValue($client);
    }
}
