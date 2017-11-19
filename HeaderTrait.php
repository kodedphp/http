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
        is_array($value) || $value = [$value];
        $instance = clone $this;

        $instance->headersMap[strtolower($name)] = $name;
        $instance->headers[$name]                = $value;

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
        is_array($value) || $value = [$value];
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

        foreach ($headers as $key => $value) {
            $instance->normalizeHeader($key, $value, false);
        }

        return $instance;
    }

    /**
     * Tries to return the nested headers as flatten array.
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
     * @param string $key
     * @param array  $value
     * @param bool   $skipKey
     *
     * @return void
     */
    protected function normalizeHeader(string $key, $value, bool $skipKey): void
    {
        if (false === $skipKey) {
            $key = strtolower(str_replace(['_', '-'], ' ', $key));
            $key = str_replace(' ', '-', ucwords($key));
        }

        $this->headersMap[strtolower($key)] = $key;
        $this->headers[$key]                = str_replace(["\r", "\n"], '', $value);
    }

    /**
     * @param iterable $headers Associative headers array
     *
     * @return void
     */
    protected function setHeaders(iterable $headers): void
    {
        if ($headers) {
            $headers = array_filter($headers, 'is_string', ARRAY_FILTER_USE_KEY);
            foreach ($headers as $header => $value) {
                $this->normalizeHeader($header, $value, false);
            }
        }
    }
}
