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

use InvalidArgumentException;
use Koded\Http\Interfaces\OutgoingRequest;
use Koded\Http\Interfaces\Request;
use Koded\Stdlib\Arguments;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequest extends ClientRequest implements OutgoingRequest, ServerRequestInterface
{

    use CookieTrait, FilesTrait;

    /** @var string */
    protected $serverSoftware = '';

    /** @var Arguments */
    protected $attributes;

    /** @var array */
    protected $queryParams = [];
    protected $parsedBody  = null;

    /**
     * ServerRequest constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($_SERVER['REQUEST_METHOD'] ?? Request::GET, $this->buildUri());
        $this->attributes = new Arguments($attributes);
        $this->extractHttpHeaders();
        $this->extractServerData();
    }

    public function getServerParams(): array
    {
        return $_SERVER;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query): ServerRequest
    {
        $instance              = clone $this;
        $instance->queryParams = array_merge($instance->queryParams, $query);

        return $instance;
    }

    public function getParsedBody(): ?array
    {
        // @see \Psr\Http\Message\ServerRequestInterface::getParsedBody()
        if (self::POST === $this->method && array_filter($this->getHeader('content-type'), function($value) {
                $value = strtolower($value);

                return in_array($value, ['application/x-www-form-urlencoded', 'multipart/form-data']);
            })
        ) {
            $this->parsedBody = $_POST;
        }

        return $this->parsedBody;
    }

    public function withParsedBody($data): ServerRequest
    {
        $instance = clone $this;

        if (is_iterable($data)) {
            $instance->parsedBody = is_array($data) ? $data : iterator_to_array($data);

            return $instance;
        }

        // If nothing was available to parse as body
        if (null === $data) {
            $instance->parsedBody = $data;

            return $instance;
        }

        throw new InvalidArgumentException('Unsupported data provided, Expects NULL, array or iterable');
    }

    public function getAttributes(): array
    {
        return $this->attributes->toArray();
    }

    public function getAttribute($name, $default = null)
    {
        return $this->attributes->get($name, $default);
    }

    public function withAttribute($name, $value): ServerRequest
    {
        $instance = clone $this;
        $instance->attributes->set($name, $value);

        return $instance;
    }

    public function withoutAttribute($name): ServerRequest
    {
        $instance = clone $this;
        $instance->attributes->delete($name);

        return $instance;
    }

    /**
     * Replace all attributes with provided
     * This method is not part of the PSR-7.
     *
     * @param array $attributes Sets all attributes in the request object
     *
     * @return Request A new immutable response instance
     */
    public function withAttributes(array $attributes): Request
    {
        $instance             = clone $this;
        $instance->attributes = new Arguments($attributes);

        return $instance;
    }

    public function isXHR(): bool
    {
        return strtoupper($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHTTPREQUEST' || false;
    }

    protected function buildUri(): Uri
    {
        if ($host = $_SERVER['SERVER_NAME'] ?? $_SERVER['SERVER_ADDR'] ?? '') {
            return new Uri('http' . ($_SERVER['HTTPS'] ?? false ? 's' : '')
                . '://' . $host
                . ':' . ($_SERVER['SERVER_PORT'] ?? 80)
                . ($_SERVER['REQUEST_URI'] ?? '')
            );
        }

        return new Uri('');
    }

    private function extractHttpHeaders(): void
    {
        foreach ($_SERVER as $k => $v) {
            // Calisthenics :)
            0 === strpos($k, 'HTTP_', 0) && $this->normalizeHeader(str_replace('HTTP_', '', $k), $v, false);
        }

        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            // ETag workaround for various broken Apache2 versions
            $this->headers['ETag']    = str_replace('-gzip', '', $_SERVER['HTTP_IF_NONE_MATCH']);
            $this->headersMap['etag'] = 'ETag';
        }

        $this->setHost();
    }

    private function extractServerData(): void
    {
        $this->protocolVersion = str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL'] ?? $this->protocolVersion);
        $this->serverSoftware  = $_SERVER['SERVER_SOFTWARE'] ?? '';
        $this->queryParams     = $_GET;
        $this->cookieParams    = $_COOKIE;

        if ($_FILES) {
            $this->uploadedFiles = $this->parseUploadedFiles($_FILES);
        }
    }
}
