<?php declare(strict_types=1);

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
use Koded\Http\Interfaces\HttpStatus;
use Throwable;


trait HeaderTrait
{
    /**
     * @var array Message headers.
     */
    protected $headers = [];

    /**
     * @var array Used for case-insensitivity header name checks
     */
    protected $headersMap = [];

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader($name): array
    {
        if (false === isset($this->headersMap[$name = strtolower($name)])) {
            return [];
        }

        if ($value = $this->headers[$this->headersMap[$name]]) {
            return (array)$value;
        }

        return [];
    }

    public function getHeaderLine($name): string
    {
        return join(',', $this->getHeader($name));
    }

    public function withHeader($name, $value): self
    {
        $instance = clone $this;
        $name     = $instance->normalizeHeaderName($name);

        $instance->headersMap[strtolower($name)] = $name;

        $instance->headers[$name] = $this->normalizeHeaderValue($name, $value);

        return $instance;
    }

    public function withHeaders(array $headers): self
    {
        $instance = clone $this;

        foreach ($headers as $name => $value) {
            $instance->normalizeHeader($name, $value, false);
        }

        return $instance;
    }

    public function withoutHeader($name): self
    {
        $instance = clone $this;
        $name     = strtolower($name);
        unset($instance->headers[$this->headersMap[$name]], $instance->headersMap[$name]);

        return $instance;
    }

    public function withAddedHeader($name, $value): self
    {
        $instance = clone $this;
        $name     = $instance->normalizeHeaderName($name);
        $value    = $instance->normalizeHeaderValue($name, $value);

        if (isset($instance->headersMap[$header = strtolower($name)])) {
            $header                     = $instance->headersMap[$header];
            $instance->headers[$header] = array_unique(array_merge((array)$instance->headers[$header], $value));
        } else {
            $instance->headersMap[$header] = $name;
            $instance->headers[$name]      = $value;
        }

        return $instance;
    }

    public function hasHeader($name): bool
    {
        return array_key_exists(strtolower($name), $this->headersMap);
    }

    public function replaceHeaders(array $headers)
    {
        $instance          = clone $this;
        $instance->headers = $instance->headersMap = [];

        foreach ($headers as $name => $value) {
            $instance->normalizeHeader($name, $value, false);
        }

        return $instance;
    }

    /**
     * Transforms the nested headers as a flatten array.
     * This method is not part of the PSR-7.
     *
     * @return array
     */
    public function getFlattenedHeaders(): array
    {
        $flattenHeaders = [];
        foreach ($this->headers as $name => $value) {
            $flattenHeaders[] = $name . ':' . join(',', (array)$value);
        }

        return $flattenHeaders;
    }

    public function getCanonicalizedHeaders(array $names = []): string
    {
        if (empty($names)) {
            $names = array_keys($this->headers);
        }

        if (!$headers = array_reduce($names, function($list, $name) {
            $name   = str_replace('_', '-', $name);
            $list[] = strtolower($name) . ':' . join(',', $this->getHeader($name));

            return $list;
        })) {
            return '';
        }

        sort($headers);

        return join("\n", $headers);
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param bool   $skipKey
     *
     * @return void
     */
    protected function normalizeHeader(string $name, $value, bool $skipKey): void
    {
        $name = str_replace(["\r", "\n", "\t"], '', trim($name));

        if (false === $skipKey) {
            $name = ucwords(str_replace('_', '-', strtolower($name)), '-');
        }

        $this->headersMap[strtolower($name)] = $name;

        $this->headers[$name] = $this->normalizeHeaderValue($name, $value);
    }

    /**
     * @param array $headers Associative headers array
     *
     * @return static
     */
    protected function setHeaders(array $headers)
    {
        foreach (array_filter($headers, 'is_string', ARRAY_FILTER_USE_KEY) as $name => $value) {
            $this->normalizeHeader($name, $value, false);
        }

        return $this;
    }

    /**
     * @param string $name
     *
     * @return string Normalized header name
     */
    protected function normalizeHeaderName($name): string
    {
        try {
            $name = str_replace(["\r", "\n", "\t"], '', trim($name));
        } catch (Throwable $e) {
            throw new InvalidArgumentException(
                sprintf('Header name must be a string, %s given', gettype($name)), HttpStatus::BAD_REQUEST
            );
        }

        if ('' === $name) {
            throw new InvalidArgumentException('Empty header name', HttpStatus::BAD_REQUEST);
        }

        return $name;
    }

    /**
     * @param string          $name
     * @param string|string[] $value
     *
     * @return array
     */
    protected function normalizeHeaderValue(string $name, $value): array
    {
        $type = gettype($value);
        switch ($type) {
            case 'array':
            case 'integer':
            case 'double':
            case 'string':
                $value = (array)$value;
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf('Invalid header value, expects string or array, "%s" given', $type), HttpStatus::BAD_REQUEST
                );
        }

        if (empty($value = array_map(function($v) {
            return trim(preg_replace('/\s+/', ' ', $v));
        }, $value))) {
            throw new InvalidArgumentException(
                sprintf('The value for header "%s" cannot be empty', $name), HttpStatus::BAD_REQUEST
            );
        }

        return $value;
    }
}
