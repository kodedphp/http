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

use Koded\Http\Interfaces\HttpMethod;
use Koded\Http\Interfaces\Request;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequest extends ClientRequest implements Request
{
    use CookieTrait, FilesTrait, ValidatableTrait;

    protected string $serverSoftware = '';
    protected array  $attributes     = [];
    protected array  $queryParams    = [];

    protected object|array|null $parsedBody = null;

    /**
     * ServerRequest constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
//        parent::__construct($_SERVER['REQUEST_METHOD'] ?? Request::GET, $this->buildUri());
        parent::__construct(HttpMethod::tryFrom($_SERVER['REQUEST_METHOD'] ?? 'GET'), $this->buildUri());
        $this->attributes = $attributes;
        $this->extractHttpHeaders($_SERVER);
        $this->extractServerData($_SERVER);
    }

    public function getServerParams(): array
    {
        return $_SERVER;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query): static
    {
        $instance              = clone $this;
        $instance->queryParams = \array_merge($instance->queryParams, $query);
        return $instance;
    }

    public function getParsedBody(): object|array|null
    {
        if ($this->useOnlyPost()) {
            return $_POST;
        }
        if (false === empty($_POST)) {
            return $_POST;
        }
        return $this->parsedBody;
    }

    public function withParsedBody($data): static
    {
        $instance = clone $this;
        if ($this->useOnlyPost()) {
            $instance->parsedBody = $_POST;
            return $instance;
        }
        // If nothing is available for the body
        if (null === $data) {
            $instance->parsedBody = null;
            return $instance;
        }
        // Supports array or iterable object
        if (\is_iterable($data)) {
            $instance->parsedBody = \is_array($data) ? $data : \iterator_to_array($data);
            return $instance;
        }
        if (\is_object($data)) {
            $instance->parsedBody = $data;
            return $instance;
        }
        throw new \InvalidArgumentException(
            \sprintf('Unsupported data provided (%s), Expects NULL, array or iterable', \gettype($data))
        );
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute($name, $value): static
    {
        $instance                    = clone $this;
        $instance->attributes[$name] = $value;
        return $instance;
    }

    public function withoutAttribute($name): static
    {
        $instance = clone $this;
        unset($instance->attributes[$name]);
        return $instance;
    }

    public function withAttributes(array $attributes): static
    {
        $instance = clone $this;
        foreach ($attributes as $name => $value) {
            $instance->attributes[$name] = $value;
        }
        return $instance;
    }

    public function isXHR(): bool
    {
        return 'XMLHTTPREQUEST' === \strtoupper($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
    }

    protected function buildUri(): Uri
    {
        if (\strpos($_SERVER['REQUEST_URI'] ?? '', '://')) {
            return new Uri($_SERVER['REQUEST_URI']);
        }
        if ($host = $_SERVER['SERVER_NAME'] ?? $_SERVER['SERVER_ADDR'] ?? '') {
            return new Uri('http' . ($_SERVER['HTTPS'] ?? $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? false ? 's' : '')
                . '://' . $host
                . ':' . ($_SERVER['SERVER_PORT'] ?? 80)
                . ($_SERVER['REQUEST_URI'] ?? '')
            );
        }
        return new Uri($_SERVER['REQUEST_URI'] ?? '');
    }

    protected function extractHttpHeaders(array $server): void
    {
        foreach ($server as $k => $v) {
            // Calisthenics :)
            \str_starts_with($k, 'HTTP_') && $this->normalizeHeader(\str_replace('HTTP_', '', $k), $v, false);
        }
        if (isset($server['HTTP_IF_NONE_MATCH'])) {
            // ETag workaround for various broken Apache2 versions
            $this->headers['ETag']    = \str_replace('-gzip', '', $server['HTTP_IF_NONE_MATCH']);
            $this->headersMap['etag'] = 'ETag';
        }
        if (isset($server['CONTENT_TYPE'])) {
            $this->headers['Content-Type']    = \strtolower($server['CONTENT_TYPE']);
            $this->headersMap['content-type'] = 'Content-Type';
        }
        $this->setHost();
    }

    protected function extractServerData(array $server): void
    {
        $this->protocolVersion = \str_ireplace('HTTP/', '', $server['SERVER_PROTOCOL'] ?? $this->protocolVersion);
        $this->serverSoftware  = $server['SERVER_SOFTWARE'] ?? '';
        $this->queryParams     = $_GET;
        $this->cookieParams    = $_COOKIE;
        if (false === $this->isSafeMethod()) {
            $this->parseInput();
        }
        if ($_FILES) {
            $this->uploadedFiles = $this->parseUploadedFiles($_FILES);
        }
    }

    /**
     * Per recommendation:
     *
     * @return bool If the request Content-Type is either
     * application/x-www-form-urlencoded or multipart/form-data
     * and the request method is POST,
     * then it MUST return the contents of $_POST
     * @see ServerRequestInterface::withParsedBody()
     *
     * @see ServerRequestInterface::getParsedBody()
     */
    protected function useOnlyPost(): bool
    {
        if (empty($contentType = $this->getHeaderLine('Content-Type'))) {
            return false;
        }
//        return $this->method === self::POST && (
        return $this->method === HttpMethod::POST && (
            \str_contains('application/x-www-form-urlencoded', $contentType) ||
            \str_contains('multipart/form-data', $contentType));
    }

    /**
     * Try to unserialize a JSON string or form encoded request body.
     * Very useful if JavaScript app stringify objects in AJAX requests.
     */
    protected function parseInput(): void
    {
        if (empty($input = $this->getRawInput())) {
            return;
        }
        // Try JSON deserialization
        $this->parsedBody = \json_decode($input, true, 512, JSON_BIGINT_AS_STRING);
        if (null === $this->parsedBody) {
            \parse_str($input, $this->parsedBody);
        }
    }

    protected function getRawInput(): string
    {
        return \file_get_contents('php://input') ?: '';
    }
}
