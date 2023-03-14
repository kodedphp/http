<?php

/*
 * This file is part of the Koded package.
 *
 * (c) Mihail Binev <mihail@kodeart.com>
 *
 * Please view the LICENSE distributed with this source code
 * for the full copyright and license information.
 *
 */

namespace Koded\Http;

/*
 *
 * Implementation of PSR-17 (HTTP Message Factories)
 * @see https://www.php-fig.org/psr/psr-17/
 *
 */

use Koded\Http\Interfaces\HttpMethod;
use Psr\Http\Message\{RequestFactoryInterface,
    RequestInterface,
    ResponseFactoryInterface,
    ResponseInterface,
    ServerRequestFactoryInterface,
    ServerRequestInterface,
    StreamFactoryInterface,
    StreamInterface,
    UploadedFileFactoryInterface,
    UploadedFileInterface,
    UriFactoryInterface,
    UriInterface};
use function array_replace;
use function strtoupper;
use const UPLOAD_ERR_OK;


class HttpFactory implements RequestFactoryInterface,
    ResponseFactoryInterface,
    ServerRequestFactoryInterface,
    StreamFactoryInterface,
    UploadedFileFactoryInterface,
    UriFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new ClientRequest(HttpMethod::tryFrom(strtoupper($method)), $uri);
    }

    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        if ($serverParams) {
            $_SERVER = array_replace($_SERVER, $serverParams);
        }
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        $_SERVER['REQUEST_URI']    = (string)$uri;
        return new ServerRequest;
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return (new ServerResponse)->withStatus($code, $reasonPhrase);
    }

    public function createStream(string $content = ''): StreamInterface
    {
        return create_stream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return new FileStream($filename, $mode);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return create_stream($resource);
    }

    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }

    public function createUploadedFile(
        StreamInterface $stream,
        ?int $size = null,
        ?int $error = UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ): UploadedFileInterface {
        return new UploadedFile([
            'tmp_name' => $stream,
            'name'     => $clientFilename,
            'type'     => $clientMediaType,
            'size'     => $size,
            'error'    => $error,
        ]);
    }
}
