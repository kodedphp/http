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
use Psr\Http\Message\UriInterface;
use Throwable;

class Uri implements UriInterface
{
    /**
     * @var string
     */
    private $scheme = '';

    /**
     * @var string
     */
    private $host = '';

    /**
     * @var int
     */
    private $port = 80;

    /**
     * @var string
     */
    private $path = '';

    /**
     * @var string
     */
    private $user = '';

    /**
     * @var string
     */
    private $pass = '';

    /**
     * @var string
     */
    private $fragment = '';

    /**
     * @var string
     */
    private $query = '';

    public function __construct(string $uri)
    {
        $uri and $this->parse($uri);
    }

    public function getScheme(): string
    {
        return strtolower($this->scheme);
    }

    public function getAuthority(): string
    {
        if (empty($this->getUserInfo())) {
            return '';
        }

        return $this->getUserInfo() . '@' . $this->host . ($this->isStandardPort() ? '' : ':' . $this->port);
    }

    public function getUserInfo(): string
    {
        return $this->user . ($this->pass ? ':' . $this->pass : '');
    }

    public function getHost(): string
    {
        return strtolower($this->host);
    }

    public function getPort(): ?int
    {
        if ((!$this->port and !$this->scheme) or $this->isStandardPort()) {
            return null;
        }

        return $this->port;
    }

    public function getPath(): string
    {
        return rawurldecode($this->path);
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return rawurldecode($this->fragment);
    }

    public function withScheme($scheme): UriInterface
    {
        $instance = clone $this;
        $instance->scheme = empty($scheme) ? null : $scheme;

        return $instance;
    }

    public function withUserInfo($user, $password = null): UriInterface
    {
        $instance = clone $this;
        $instance->user = empty($user) ? null : (string)$user;
        $instance->pass = empty($user) ? null : (string)$password;

        return $instance;
    }

    public function withHost($host): UriInterface
    {
        $instance = clone $this;
        $instance->host = empty($host) ? null : (string)$host;

        return $instance;
    }

    public function withPort($port): UriInterface
    {
        $instance = clone $this;

        if (null === $port) {
            $instance->port = null;

            return $instance;
        }

        if (!is_int($port) and $port < 1) {
            throw new InvalidArgumentException('Invalid port');
        }

        $instance->port = (int)$port;

        return $instance;
    }

    public function withPath($path): UriInterface
    {
        $instance = clone $this;
        $instance->path = $path;

        return $instance;
    }

    public function withQuery($query): UriInterface
    {
        try {
            $query = rawurldecode($query);
        } catch (Throwable $e) {
            throw new InvalidArgumentException('The provided query string is invalid');
        }


        $instance = clone $this;
        $instance->query = empty($query) ? '' : (string)$query;

        return $instance;
    }

    public function withFragment($fragment): UriInterface
    {
        $instance = clone $this;
        $instance->fragment = empty($fragment) ? '' : $fragment;

        return $instance;
    }

    public function __toString()
    {
        return sprintf('%s%s%s%s%s%s',
            $this->scheme ? $this->getScheme() . '://' : '',
            $this->user ? $this->getAuthority() : $this->getHost(),
            $this->getPort(),
            $this->fixPath(),
            $this->query ? '?' . $this->query : null,
            $this->fragment ? '#' . ltrim($this->fragment, '#') : null
        );
    }

    private function parse(string $uri)
    {
        if (false === $parts = parse_url($uri)) {
            throw new InvalidArgumentException('Please provide a valid URI', HttpStatus::BAD_REQUEST);
        }

        foreach ($parts as $k => $v) {
            $this->$k = $v;
        }
    }

    private function isStandardPort(): bool
    {
        return in_array($this->port, [80, 443, 21, 23, 70, 110, 119, 143, 389]);
    }

    /**
     * - If the path is rootless and an authority is present, the path MUST
     *   be prefixed by "/".
     * - If the path is starting with more than one "/" and no authority is
     *   present, the starting slashes MUST be reduced to one.
     *
     * @return string
     */
    private function fixPath(): string
    {
        $path = $this->getPath();

        if ($this->user and $path[0] !== '/') {
            return '/' . $path;
        }

        return '/' . ltrim($path, '/');
    }
}
