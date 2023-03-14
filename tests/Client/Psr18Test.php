<?php

namespace Tests\Koded\Http\Client;

use Koded\Http\{Client\ClientFactory, Client\Psr18Exception, ClientRequest, ServerRequest};
use Koded\Http\Interfaces\{ClientType, HttpMethod, HttpStatus};
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\{ClientExceptionInterface, ClientInterface};

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
    public function test_should_fail_with_server_request_instance($client)
    {
        $this->expectException(Psr18Exception::class);
        $this->expectExceptionCode(HttpStatus::FAILED_DEPENDENCY);

        $client->sendRequest(new ServerRequest);
    }

    /**
     * @dataProvider clients
     *
     * @param ClientInterface $client
     *
     * @throws ClientExceptionInterface
     */
    public function test_should_pass_with_client_request_instance($client)
    {
//        $response = $client->sendRequest(new ClientRequest('GET', 'http://example.com'));
        $response = $client->sendRequest(new ClientRequest(HttpMethod::GET, 'http://example.com'));
        $this->assertSame(HttpStatus::OK, $response->getStatusCode());
    }

    /**
     * @dataProvider clients
     *
     * @param ClientInterface $client
     *
     * @throws ClientExceptionInterface
     */
    public function test_exception_with_client_request_instance_and_empty_url($client)
    {
        $this->expectException(Psr18Exception::class);
        $this->expectExceptionCode(HttpStatus::FAILED_DEPENDENCY);

        $client->sendRequest(new ClientRequest(HttpMethod::GET, ''));
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
        $request = new ClientRequest(HttpMethod::GET, '');

        try {
            $client->sendRequest($request);
        } catch (\Exception $e) {
            $this->assertInstanceOf(Psr18Exception::class, $e);;
            $this->assertSame($request, $e->getRequest());
        }
    }

    public function clients()
    {
        return [
            [
                (new ClientFactory(ClientType::PHP))
                    ->client()
                    ->timeout(3)
                    ->maxRedirects(2)
            ],
            [
                (new ClientFactory(ClientType::CURL))
                    ->client()
                    ->timeout(3)
                    ->maxRedirects(2)
            ]
        ];
    }

    protected function setUp(): void
    {
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = '';
    }
}
