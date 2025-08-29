<?php

namespace Tests\Koded\Http;

use Koded\Http\{HTTPError, HTTPMethodNotAllowed};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use function Koded\Http\create_stream;
use function serialize;
use function unserialize;

class HTTPErrorSerializationTest extends TestCase
{
    public function test_default_object_serialization()
    {
        $expected = new HTTPError(424);
        $actual = unserialize(serialize($expected));

        $this->assertEquals($expected, $actual);
        $this->assertNotSame($expected, $actual,
            '(the instances are not same)');
    }

    public function test_full_object_serialization()
    {
        $expected = new HTTPMethodNotAllowed(['PUT'],
            instance: '/test',
            title: 'HTTPError Test',
            detail: 'A unit test for serializing the HTTPError object',
            type: '/url/for/more/details',
            headers: ['X-Test' => 'true']
        );
        $expected->setMember('foo', 'bar');
        $expected->setMember('bar', 'qux');

        $actual = unserialize(serialize($expected));
        $this->assertEquals($expected, $actual);
        $this->assertNotSame($expected, $actual,
            '(the instances are not same)');
    }

    public function test_stringable_implementation()
    {
        $err = new HTTPError(
            status: 200,
            title: 'Test Error',
            detail: 'A unit test for serializing the HTTPError object',
            instance: '/test',
            type: '/test/errors',
            headers: ['X-Test' => 'true'],
        );

        $this->assertJson((string) $err);
        $this->assertJsonStringEqualsJsonString(
            '{"status":200,"instance":"/test","detail":"A unit test for serializing the HTTPError object","title":"Test Error","type":"/test/errors"}',
            (string) $err,
        'headers are not serialized as expected');
    }

    /**
     * @depends test_stringable_implementation
     */
    public function test_conversion_to_stream()
    {
        $err = new HTTPError(
            status: 200,
            title: 'Test Error',
            detail: 'A unit test for serializing the HTTPError object',
            instance: '/test',
            type: '/test/errors',
            headers: ['X-Test' => 'true'],
        );

        $stream = create_stream($err);

        $this->assertEquals(
            '{"status":200,"instance":"/test","detail":"A unit test for serializing the HTTPError object","title":"Test Error","type":"/test/errors"}',
            $stream->getContents(),
            'headers are not included as expected');
    }
}
