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

use Koded\Http\Interfaces\HttpStatus;

trait HeaderTrait
{
    /**
     * @var array Message headers.
     */
    protected array $headers = [];

    /**
     * @var array Used for case-insensitivity header name checks
     */
    protected array $headersMap = [];

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader($name): array
    {
        if (false === isset($this->headersMap[$name = \strtolower($name)])) {
            return [];
        }
        $value = $this->headers[$this->headersMap[$name]];
        if (\is_string($value)) {
            return empty($value) ? [] : [$value];
        }
        $header = [];
        foreach ($value as $_ => $v) {
            $header[] = \join(',', (array)$v);
        }
        return $header;
    }

    public function getHeaderLine($name): string
    {
        return \join(',', $this->getHeader($name));
    }

    public function withHeader($name, $value): static
    {
        $instance = clone $this;
        $name     = $instance->normalizeHeaderName($name);

        $instance->headersMap[\strtolower($name)] = $name;

        $instance->headers[$name] = $this->normalizeHeaderValue($name, $value);
        return $instance;
    }

    public function withHeaders(array $headers): static
    {
        $instance = clone $this;
        foreach ($headers as $name => $value) {
            $instance->normalizeHeader($name, $value, false);
        }
        return $instance;
    }

    public function withoutHeader($name): static
    {
        $instance = clone $this;
        $name     = \strtolower($name);
        if (isset($instance->headersMap[$name])) {
            unset(
                $instance->headers[$this->headersMap[$name]],
                $instance->headersMap[$name]
            );
        }
        return $instance;
    }

    public function withAddedHeader($name, $value): static
    {
        $instance = clone $this;
        $name     = $instance->normalizeHeaderName($name);
        $value    = $instance->normalizeHeaderValue($name, $value);
        if (isset($instance->headersMap[$header = \strtolower($name)])) {
            $header                     = $instance->headersMap[$header];
            $instance->headers[$header] = \array_unique(
                @\array_merge_recursive($instance->headers[$header], $value)
            );
        } else {
            $instance->headersMap[$header] = $name;
            $instance->headers[$name]      = $value;
        }
        return $instance;
    }

    public function hasHeader($name): bool
    {
        return \array_key_exists(\strtolower($name), $this->headersMap);
    }

    public function replaceHeaders(array $headers): static
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
            $flattenHeaders[] = $name . ':' . \join(',', (array)$value);
        }
        return $flattenHeaders;
    }

    public function getCanonicalizedHeaders(array $names = []): string
    {
        if (empty($names)) {
            $names = \array_keys($this->headers);
        }
        if (!$headers = \array_reduce($names, function($list, $name) {
            $name   = \str_replace('_', '-', $name);
            $list[] = \strtolower($name) . ':' . \join(',', $this->getHeader($name));
            return $list;
        })) {
            return '';
        }
        \sort($headers);
        return \join("\n", $headers);
    }

    /**
     * @param string          $name
     * @param string|string[] $value
     * @param bool            $skipKey
     *
     * @return void
     */
    protected function normalizeHeader(string $name, array|string $value, bool $skipKey): void
    {
        $name = \str_replace(["\r", "\n", "\t"], '', \trim($name));
        if (false === $skipKey) {
            $name = \ucwords(\str_replace('_', '-', \strtolower($name)), '-');
        }
        $this->headersMap[\strtolower($name)] = $name;

        $this->headers[$name] = $this->normalizeHeaderValue($name, $value);
    }

    /**
     * @param array $headers Associative headers array
     *
     * @return static
     */
    protected function setHeaders(array $headers): static
    {
        try {
            foreach ($headers as $name => $value) {
                $this->normalizeHeader($name, $value, false);
            }
            return $this;
        } catch (\TypeError $e) {
            throw new \InvalidArgumentException($e->getMessage(), HttpStatus::BAD_REQUEST, $e);
        }
    }

    /**
     * @param string $name
     *
     * @return string Normalized header name
     */
    protected function normalizeHeaderName(string $name): string
    {
        $name = \str_replace(["\r", "\n", "\t"], '', \trim($name));
        if ('' !== $name) {
            return $name;
        }
        throw new \InvalidArgumentException('Empty header name', HttpStatus::BAD_REQUEST);
    }

    /**
     * @param string          $name
     * @param string|string[] $value
     *
     * @return array
     */
    protected function normalizeHeaderValue(string $name, mixed $value): array
    {
//        if (false === \is_array($value)) {
            $value = (array)$value;
//        }
        try {
            if (empty($value = \array_map(fn($v): string => \trim(\preg_replace('/\s+/', ' ', $v)), $value))) {
                throw new \InvalidArgumentException(
                    \sprintf('The value for header "%s" cannot be empty', $name),
                    HttpStatus::BAD_REQUEST);
            }
            return $value;
        } catch (\TypeError $e) {
            throw new \InvalidArgumentException(
                \sprintf('Invalid value for header "%s", expects a string or array of strings', $name),
                HttpStatus::BAD_REQUEST, $e);
        }
    }
}
