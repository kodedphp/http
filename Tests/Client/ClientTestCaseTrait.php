<?php

namespace Koded\Http\Client;

use Koded\Http\{Interfaces\HttpRequestClient, StatusCode, Uri};
use ReflectionClass;
use function Koded\Http\create_stream;

/**
 * Trait ClientTestCaseTrait ensures some consistent behaviour
 * across the HTTP client implementations.
 *
 */
trait ClientTestCaseTrait
{

    /** @var HttpRequestClient */
    private $SUT;

    public function test_read_on_success()
    {
        $response = $this->SUT->read();

        $this->assertSame(StatusCode::OK, $response->getStatusCode(), (string)$response->getBody());
        $this->assertContains('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertGreaterThan(0, (string)$response->getBody()->getSize());
    }

    public function test_should_exit_on_bad_url()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Please provide a valid URI');
        $this->expectExceptionCode(StatusCode::BAD_REQUEST);

        $this->SUT->withUri(new Uri('scheme://host:junk'));
    }

    public function test_should_exit_on_bad_request()
    {
        /** @var HttpRequestClient $SUT */
        $SUT = $this->SUT->withBody(create_stream(json_encode(['foo' => 'bar'])));

        $badResponse = $SUT->read();

        $this->assertSame(StatusCode::BAD_REQUEST, $badResponse->getStatusCode(), get_class($SUT));
        $this->assertContains('failed to open stream: you should not set the message body with safe HTTP methods',
            (string)$badResponse->getBody());
    }

    public function test_content_type_extraction()
    {
        $response = $this->SUT->read();

        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    private function getOptions(): array
    {
        $proto   = new ReflectionClass($this->SUT);
        $options = $proto->getProperty('options');
        $options->setAccessible(true);

        return $options->getValue($this->SUT);
    }
}
