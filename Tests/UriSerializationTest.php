<?php

namespace Koded\Http;

use PHPUnit\Framework\TestCase;

class UriSerializationTest extends TestCase
{
    public function test_json_serialization_with_all_the_things()
    {
        $uri = new Uri('https://username:password@example.com:8080/foo/bar?a=1234&b=5678#baz');
;
        $this->assertSame([
            'scheme' => 'https',
            'host' => 'example.com',
            'port' => 8080,
            'path' => '/foo/bar',
            'user' => 'username',
            'pass' => 'password',
            'fragment' => 'baz',
            'query' => 'a=1234&b=5678'
        ], $uri->jsonSerialize());

        $this->assertJsonStringEqualsJsonString(
            '{"scheme":"https","host":"example.com","port":8080,"path":"/foo/bar","user":"username","pass":"password","fragment":"baz","query":"a=1234&b=5678"}',
            json_encode($uri, JSON_UNESCAPED_SLASHES)
        );
    }

    public function test_json_serialization_with_username_and_host()
    {
        $uri = new Uri('http://username@example.com');

        $this->assertSame([
            'scheme' => 'http',
            'host' => 'example.com',
            'user' => 'username',
        ], $uri->jsonSerialize());

        $this->assertJsonStringEqualsJsonString(
            '{"scheme":"http","host":"example.com","user":"username"}',
            json_encode($uri, JSON_UNESCAPED_SLASHES)
        );
    }

    public function test_json_serialization_with_no_path()
    {
        $uri = new Uri('https://example.com');

        $this->assertSame([
            'scheme' => 'https',
            'host' => 'example.com',
        ], $uri->jsonSerialize());

        $this->assertJsonStringEqualsJsonString(
            '{"scheme":"https","host":"example.com"}',
            json_encode($uri, JSON_UNESCAPED_SLASHES)
        );
    }

    public function test_json_serialization_with_slash_path()
    {
        $uri = new Uri('https://example.com/');

        $this->assertSame([
            'scheme' => 'https',
            'host' => 'example.com',
            'path' => '/'
        ], $uri->jsonSerialize());

        $this->assertJsonStringEqualsJsonString(
            '{"scheme":"https","host":"example.com","path":"/"}',
            json_encode($uri, JSON_UNESCAPED_SLASHES)
        );
    }

    public function test_json_serialization_with_standard_port()
    {
        $uri = new Uri('https://example.com:21');

        $this->assertSame([
            'scheme' => 'https',
            'host' => 'example.com',
        ], $uri->jsonSerialize(),
            'The standard port is omitted'
        );

        $this->assertJsonStringEqualsJsonString(
            '{"scheme":"https","host":"example.com"}',
            json_encode($uri, JSON_UNESCAPED_SLASHES)
        );
    }
}
