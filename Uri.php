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
use JsonSerializable;
use Koded\Http\Interfaces\HttpStatus;
use Psr\Http\Message\UriInterface;
use Throwable;

class Uri implements UriInterface, JsonSerializable
{
    const STANDARD_PORTS = [80, 443, 21, 23, 70, 110, 119, 143, 389];

    private string $scheme = '';
    private string $host = '';
    private ?int $port = 80;
    private string $path = '';
    private string $user = '';
    private string $pass = '';
    private string $fragment = '';
    private string $query = '';

    public function __construct(string $uri)
    {
        $uri && $this->parse($uri);
    }

    public function __toString()
    {
        return \sprintf('%s%s%s%s%s',
            $this->scheme ? ($this->getScheme() . '://') : '',
            $this->getAuthority() ?: $this->getHostWithPort(),
            $this->getPath(),
            \strlen($this->query) ? ('?' . $this->query) : '',
            \strlen($this->fragment) ? ('#' . $this->fragment) : ''
        );
    }

    public function getScheme(): string
    {
        return \strtolower($this->scheme);
    }

    public function getAuthority(): string
    {
        $userInfo = $this->getUserInfo();
        if (0 === \strlen($userInfo)) {
            return '';
        }
        return $userInfo . '@' . $this->getHostWithPort();
    }

    public function getUserInfo(): string
    {
        if (0 === \strlen($this->user)) {
            return '';
        }
        return \trim($this->user . ':' . $this->pass, ':');
    }

    public function getHost(): string
    {
        return \strtolower($this->host);
    }

    public function getPort(): ?int
    {
        if (!$this->scheme && !$this->port) {
            return null;
        }
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->reduceSlashes($this->path);
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme($scheme): UriInterface
    {
        if (null !== $scheme && false === \is_string($scheme)) {
            throw new InvalidArgumentException('Invalid URI scheme', 400);
        }

        $instance         = clone $this;
        $instance->scheme = (string)$scheme;
        return $instance;
    }

    public function withUserInfo($user, $password = null): UriInterface
    {
        $instance       = clone $this;
        $instance->user = (string)$user;
        $instance->pass = (string)$password;

        // If the path is rootless and an authority is present,
        // the path MUST be prefixed with "/"
        if ('/' !== ($instance->path[0] ?? '')) {
            $instance->path = '/' . $instance->path;
        }
        return $instance;
    }

    public function withHost($host): UriInterface
    {
        $instance       = clone $this;
        $instance->host = (string)$host;
        return $instance;
    }

    public function withPort($port): UriInterface
    {
        $instance = clone $this;
        if (null === $port) {
            $instance->port = null;
            return $instance;
        }
        if (false === \is_int($port) || $port < 1) {
            throw new InvalidArgumentException('Invalid port');
        }
        $instance->port = $port;
        return $instance;
    }

    public function withPath($path): UriInterface
    {
        $instance       = clone $this;
        $instance->path = $this->fixPath((string)$path);
        return $instance;
    }

    public function withQuery($query): UriInterface
    {
        try {
            $query = \rawurldecode($query);
        } catch (Throwable) {
            throw new InvalidArgumentException('The provided query string is invalid');
        }
        $instance        = clone $this;
        $instance->query = (string)$query;
        return $instance;
    }

    public function withFragment($fragment): UriInterface
    {
        $instance           = clone $this;
        $instance->fragment = \str_replace(['#', '%23'], '', $fragment);
        return $instance;
    }

    private function parse(string $uri)
    {
        if (false === $parts = \parse_url($uri)) {
            throw new InvalidArgumentException('Please provide a valid URI', HttpStatus::BAD_REQUEST);
        }
        foreach ($parts as $k => $v) {
            $this->$k = $v;
        }
        $this->path = $this->fixPath($parts['path'] ?? '');
        if ($this->isStandardPort()) {
            $this->port = null;
        }
    }

    private function fixPath(string $path): string
    {
        if (empty($path)) {
            return $path;
        }
        // Percent encode the path
        $path = \explode('/', $path);
        foreach ($path as $k => $part) {
            $path[$k] = \str_contains($part, '%') ? $part : \rawurlencode($part);
        }
        // TODO remove the entry script from the path?
        $path = \str_replace('/index.php', '', \join('/', $path));
        return $path;
    }

    private function reduceSlashes(string $path): string
    {
        if ('/' === ($path[0] ?? '') && 0 === \strlen($this->user)) {
            return \preg_replace('/\/+/', '/', $path);
        }
        return $path;
    }

    private function getHostWithPort(): string
    {
        if ($this->port) {
            return $this->host . ($this->isStandardPort() ? '' : ':' . $this->port);
        }
        return $this->host;
    }

    private function isStandardPort(): bool
    {
        return \in_array($this->port, static::STANDARD_PORTS);
    }

    public function jsonSerialize()
    {
        return \array_filter([
            'scheme' => $this->getScheme(),
            'host' => $this->getHost(),
            'port' => $this->getPort(),
            'path' => $this->getPath(),
            'user' => $this->user,
            'pass' => $this->pass,
            'fragment' => $this->fragment,
            'query' => $this->query,
        ]);
    }
}
