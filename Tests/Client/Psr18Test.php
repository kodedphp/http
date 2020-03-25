<?php

namespace Koded\Http\Client;

use Koded\Http\{ClientRequest, ServerRequest, StatusCode};
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\{ClientExceptionInterface, ClientInterface};
use Psr\Http\Message\{RequestInterface, ResponseInterface};

/**
 * @group internet
 */
class Psr18Test extends TestCase
{
    /**
     * @dataProvider clients
     *
     * @param ClientInterface $client
     *
     * @throws ClientExceptionInterface
     */
    public function test_with_server_request_instance($client)
    {
        $response = $client->sendRequest(new ServerRequest);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(StatusCode::OK, $response->getStatusCode());
    }

    /**
     * @dataProvider clients
     *
     * @param ClientInterface $client
     *
     * @throws ClientExceptionInterface
     */
    public function test_with_client_request_instance($client)
    {
        $response = $client->sendRequest(new ClientRequest('GET', 'http://example.com'));

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(StatusCode::OK, $response->getStatusCode());
    }

    /**
     * @dataProvider clients
     *
     * @param ClientInterface $client
     *
     * @throws ClientExceptionInterface
     */
    public function test_exception_with_server_request_instance($client)
    {
        $this->expectException(Psr18Exception::class);
        $this->expectExceptionCode(StatusCode::FAILED_DEPENDENCY);

        $_SERVER['SERVER_NAME'] = '';
        $client->sendRequest(new ServerRequest);
    }

    /**
     * @dataProvider clients
     *
     * @param ClientInterface $client
     *
     * @throws ClientExceptionInterface
     */
    public function test_exception_with_client_request_instance($client)
    {
        $this->expectException(Psr18Exception::class);
        $this->expectExceptionCode(StatusCode::FAILED_DEPENDENCY);

        $client->sendRequest(new ClientRequest('GET', ''));
    }

    /**
     * @dataProvider clients
     *
     * @param ClientInterface $client
     *
     * @throws ClientExceptionInterface
     */
    public function test_exception_class_methods($client)
    {
        try {
            $client->sendRequest(new ClientRequest('GET', ''));
        } catch (Psr18Exception $e) {
            $request = $e->getRequest();

            $this->assertInstanceOf(RequestInterface::class, $request);
            $this->assertGreaterThan(StatusCode::OK, $e->getCode());
        }
    }


    public function clients()
    {
        return [
            [
                (new ClientFactory(ClientFactory::PHP))
                    ->psr18()
                    ->timeout(3)
                    ->maxRedirects(1)
            ],
            [
                (new ClientFactory(ClientFactory::CURL))
                    ->psr18()
                    ->timeout(3)
                    ->maxRedirects(1)
            ]
        ];
    }

    protected function setUp()
    {
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
    }
}
