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
use Koded\Http\Interfaces\Request;
use Psr\Http\Message\ServerRequestInterface;


class ServerRequest extends ClientRequest implements Request
{
    use CookieTrait, FilesTrait, ValidatableTrait;

    /** @var string */
    protected $serverSoftware = '';

    /** @var array */
    protected $attributes = [];

    /** @var array */
    protected $queryParams = [];

    /** @var null|array */
    protected $parsedBody;

    /**
     * ServerRequest constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($_SERVER['REQUEST_METHOD'] ?? Request::GET, $this->buildUri());
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

    public function withQueryParams(array $query): ServerRequest
    {
        $instance              = clone $this;
        $instance->queryParams = array_merge($instance->queryParams, $query);

        return $instance;
    }

    public function getParsedBody()
    {
        if ($this->useOnlyPost()) {
            return $_POST;
        }

        if (false === empty($_POST)) {
            return $_POST;
        }

        return $this->parsedBody;
    }

    public function withParsedBody($data): ServerRequest
    {
        $instance = clone $this;

        if ($this->useOnlyPost()) {
            $instance->parsedBody = $_POST;

            return $instance;
        }

        // Supports only array or iterable object. Also normalize to array
        if (is_iterable($data)) {
            $instance->parsedBody = is_array($data) ? $data : iterator_to_array($data);

            return $instance;
        }

        // If nothing is available for the body
        if (null === $data) {
            $instance->parsedBody = null;

            return $instance;
        }

        if (is_object($data)) {
            $instance->parsedBody = $data;

            return $instance;
        }

        throw new InvalidArgumentException('Unsupported data provided, Expects NULL, array or iterable');
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute($name, $value): ServerRequest
    {
        $instance                    = clone $this;
        $instance->attributes[$name] = $value;

        return $instance;
    }

    public function withoutAttribute($name): ServerRequest
    {
        $instance = clone $this;
        unset($instance->attributes[$name]);

        return $instance;
    }

    public function withAttributes(array $attributes): Request
    {
        $instance             = clone $this;
        $instance->attributes = $attributes;

        return $instance;
    }

    public function isXHR(): bool
    {
        return strtoupper($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHTTPREQUEST' or false;
    }

    protected function buildUri(): Uri
    {
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '://')) {
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
            0 === strpos($k, 'HTTP_', 0) and $this->normalizeHeader(str_replace('HTTP_', '', $k), $v, false);
        }

        unset($this->headers['X-Forwarded-For'], $this->headers['X-Forwarded-Proto']);
        unset($this->headersMap['x-forwarded-for'], $this->headersMap['x-forwarded-proto']);

        if (isset($server['HTTP_IF_NONE_MATCH'])) {
            // ETag workaround for various broken Apache2 versions
            $this->headers['ETag']    = str_replace('-gzip', '', $server['HTTP_IF_NONE_MATCH']);
            $this->headersMap['etag'] = 'ETag';
        }

        if (isset($server['CONTENT_TYPE'])) {
            $this->headers['Content-Type']    = strtolower($server['CONTENT_TYPE']);
            $this->headersMap['content-type'] = 'Content-Type';
        }

        $this->setHost();
    }

    protected function extractServerData(array $server): void
    {
        $this->protocolVersion = str_ireplace('HTTP/', '', $server['SERVER_PROTOCOL'] ?? $this->protocolVersion);
        $this->serverSoftware  = $server['SERVER_SOFTWARE'] ?? '';
        $this->queryParams     = $_GET;
        $this->cookieParams    = $_COOKIE;

        if (false === $this->isSafeMethod) {
            $this->parseInput();
        }

        if ($_FILES) {
            $this->uploadedFiles = $this->parseUploadedFiles($_FILES);
        }
    }

    /**
     * Per recommendation:
     *
     * @see ServerRequestInterface::getParsedBody()
     * @see ServerRequestInterface::withParsedBody()
     *
     * @return bool If the request Content-Type is either
     * application/x-www-form-urlencoded or multipart/form-data
     * and the request method is POST,
     * then it MUST return the contents of $_POST
     */
    protected function useOnlyPost(): bool
    {
        if ($this->isSafeMethod) {
            return false;
        }

        if (empty($contentType = $this->getHeaderLine('Content-Type'))) {
            return false;
        }

        return $this->method === self::POST && (
                false !== strpos('application/x-www-form-urlencoded', $contentType) ||
                false !== strpos('multipart/form-data', $contentType));
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
        $this->parsedBody = json_decode($input, true, 512, JSON_BIGINT_AS_STRING);

        if (null === $this->parsedBody) {
            parse_str($input, $this->parsedBody);
        }
    }

    protected function getRawInput(): string
    {
        return file_get_contents('php://input') ?: '';
    }
}
