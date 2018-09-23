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
        return join(', ', $this->getHeader($name));
    }

    public function withHeader($name, $value): self
    {
        $instance = clone $this;

        $instance->headersMap[strtolower($name)] = $name;
        $instance->headers[$name]                = (array)$value;

        return $instance;
    }

    public function withHeaders(array $headers): self
    {
        $instance = clone $this;

        foreach ($headers as $name => $value) {
            $instance->headersMap[strtolower($name)] = $name;
            $instance->headers[$name]                = (array)$value;
        }

        return $instance;
    }

    public function withoutHeader($name): self
    {
        $instance = clone $this;
        unset($instance->headersMap[strtolower($name)], $instance->headers[$name]);

        return $instance;
    }

    public function withAddedHeader($name, $value): self
    {
        $value    = (array)$value;
        $instance = clone $this;

        if (isset($instance->headersMap[$header = strtolower($name)])) {
            $instance->headers[$name] = array_merge((array)$this->headers[$name], $value);
        } else {
            $instance->headersMap[strtolower($name)] = $name;
            $instance->headers[$name]                = $value;
        }

        return $instance;
    }

    public function hasHeader($name): bool
    {
        return array_key_exists(strtolower($name), $this->headersMap);
    }

    /**
     * Replaces all headers with provided ones.
     * This method is not part of the PSR-7.
     *
     * @param array $headers
     *
     * @return $this
     */
    public function replaceHeaders(array $headers): self
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
            $flattenHeaders[] = $name . ': ' . join(',', (array)$value);
        }

        return $flattenHeaders;
    }

    /**
     * @param string $name
     * @param array  $value
     * @param bool   $skipKey
     *
     * @return void
     */
    protected function normalizeHeader(string $name, $value, bool $skipKey): void
    {
        if (false === $skipKey) {
            $name = ucwords(str_replace('_', '-', strtolower($name)), '-');
        }

        $this->headersMap[strtolower($name)] = $name;
        $this->headers[$name]                = str_replace(["\r", "\n"], '', $value);
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
}
