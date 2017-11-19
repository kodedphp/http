<?php

namespace Koded\Http\Client;

use Koded\Http\HttpStatus;
use Koded\Http\Interfaces\HttpRequestClient;
use Koded\Http\Uri;
use ReflectionClass;
use function Koded\Http\create_stream;

require_once __DIR__ . '/../../vendor/koded/stdlib/functions-dev.php';

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

        $this->assertSame(HttpStatus::OK, $response->getStatusCode());
        $this->assertContains('text/html', $response->getHeader('Content-Type'));
        $this->assertGreaterThan(0, (string)$response->getBody()->getSize());
    }

    public function __test_should_exit_on_bad_url()
    {
        $SUT = $this->SUT->withUri(new Uri('scheme://host:junk'));
        $bad = $SUT->read();

        $this->assertSame(HttpStatus::BAD_REQUEST, $bad->getStatusCode());
        $this->assertContains('failed to open stream:', (string)$bad->getBody());
    }

    public function test_should_exit_on_bad_request()
    {
        /** @var HttpRequestClient $SUT */
        $SUT         = $this->SUT->withBody(create_stream(json_encode(['foo' => 'bar'])));
        $badResponse = $SUT->read();

        $this->assertSame(HttpStatus::BAD_REQUEST, $badResponse->getStatusCode(), get_class($SUT));
        $this->assertContains('failed to open stream: you should not set the message body with safe HTTP methods',
            (string)$badResponse->getBody());
    }

    private function getOptions(): array
    {
        $proto   = new ReflectionClass($this->SUT);
        $options = $proto->getProperty('options');
        $options->setAccessible(true);

        return $options->getValue($this->SUT);
    }
}
