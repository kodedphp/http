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

    public function withHeader($name, $value)
    {
        is_array($value) or $value = [$value];
        $instance = clone $this;

        $instance->headersMap[strtolower($name)] = $name;
        $instance->headers[$name]                = $value;

        return $instance;
    }

    public function withoutHeader($name)
    {
        $instance = clone $this;
        unset($instance->headersMap[strtolower($name)], $instance->headers[$name]);

        return $instance;
    }

    public function withAddedHeader($name, $value)
    {
        is_array($value) or $value = [$value];
        $instance = clone $this;

        if (isset($instance->headersMap[$header = strtolower($name)])) {
            $instance->headers[$name] = array_merge($this->headers[$name], $value);
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
     * @param string $key
     * @param array  $value
     * @param bool   $skipKey
     *
     * @return void
     */
    protected function normalizeHeader(string $key, $value, bool $skipKey)
    {
        if (false === $skipKey) {
            $key = strtolower(str_replace(['_', '-'], ' ', $key));
            $key = str_replace(' ', '-', ucwords($key));
        }

        $this->headers[$key] = str_replace(["\r", "\n"], '', $value);

        $this->headersMap[strtolower($key)] = $key;
    }
}
