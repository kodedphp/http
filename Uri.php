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
use JsonSerializable;
use Koded\Http\Interfaces\HttpStatus;
use Psr\Http\Message\UriInterface;
use Throwable;
use function array_filter;
use function explode;
use function in_array;
use function is_int;
use function join;
use function mb_strlen;
use function parse_url;
use function preg_replace;
use function rawurldecode;
use function rawurlencode;
use function sprintf;
use function str_contains;
use function str_replace;
use function strtolower;
use function trim;

class Uri implements UriInterface, JsonSerializable
{
    public const STANDARD_PORTS = [80, 443, 21, 23, 70, 110, 119, 143, 389];

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

    public function __toString(): string
    {
        return sprintf('%s%s%s%s%s',
            $this->scheme ? ($this->getScheme() . '://') : '',
            $this->getAuthority() ?: $this->getHostWithPort(),
            $this->getPath(),
            mb_strlen($this->query) ? ('?' . $this->query) : '',
            mb_strlen($this->fragment) ? ('#' . $this->fragment) : ''
        );
    }

    public function getScheme(): string
    {
        return strtolower($this->scheme);
    }

    public function getAuthority(): string
    {
        return ($userInfo = $this->getUserInfo())
            ? $userInfo . '@' . $this->getHostWithPort()
            : '';
    }

    public function getUserInfo(): string
    {
        if (empty($this->user)) {
            return '';
        }
        return trim(rawurlencode($this->user) . ':' . rawurlencode($this->pass), ':');
    }

    public function getHost(): string
    {
        return mb_strtolower($this->host);
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
        $path = $this->path;
        // If the path is rootless and an authority is present,
        // the path MUST be prefixed with "/"
        if ($this->user && '/' !== ($path[0] ?? '')) {
            return '/' . $path;
        }
        // If the path is starting with more than one "/" and no authority is
        // present, the starting slashes MUST be reduced to one
        if (!$this->user && '/' === ($path[0] ?? '') && '/' === ($path[1] ?? '')) {
            $path = preg_replace('/\/+/', '/', $path);
        }
        // Percent encode the path
        $path = explode('/', $path);
        foreach ($path as $k => $part) {
            $path[$k] = str_contains($part, '%') ? $part : rawurlencode($part);
        }
        // TODO remove the entry script from the path?
        return str_replace('/index.php', '', join('/', $path));
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme(string $scheme): UriInterface
    {
        $instance         = clone $this;
        $instance->scheme = $scheme;
        return $instance;
    }

    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $instance       = clone $this;
        $instance->user = rawurldecode($user);
        $instance->pass = rawurldecode((string)$password);
        return $instance;
    }

    public function withHost(string $host): UriInterface
    {
        $instance       = clone $this;
        $instance->host = $host;
        return $instance;
    }

    public function withPort(?int $port): UriInterface
    {
        $instance = clone $this;
        if (null === $port) {
            $instance->port = null;
            return $instance;
        }
        if ($port < 1) {
            throw new InvalidArgumentException('Invalid port', HttpStatus::BAD_REQUEST);
        }
        $instance->port = $port;
        return $instance;
    }

    public function withPath(string $path): UriInterface
    {
        $instance       = clone $this;
        $instance->path = $path;
        return $instance;
    }

    public function withQuery(string $query): UriInterface
    {
        try {
            $query = rawurldecode($query);
        } catch (Throwable) {
            throw new InvalidArgumentException(
                'The provided query string is invalid',
                HttpStatus::BAD_REQUEST);
        }
        $instance        = clone $this;
        $instance->query = (string)$query;
        return $instance;
    }

    public function withFragment(string $fragment): UriInterface
    {
        $instance           = clone $this;
        $instance->fragment = str_replace(['#', '%23'], '', $fragment);
        return $instance;
    }

    private function parse(string $uri)
    {
        if (false === $parts = parse_url($uri)) {
            throw new InvalidArgumentException(
                'Please provide a valid URI',
                HttpStatus::BAD_REQUEST);
        }
        $this->port = (int) ($parts['port'] ?? 443);
        unset($parts['port']);
        foreach ($parts as $k => $v) {
            $this->$k = trim($v);
        }
        if ($this->isStandardPort()) {
            $this->port = null;
        }
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
        return in_array($this->port, static::STANDARD_PORTS);
    }

    public function jsonSerialize(): mixed
    {
        return array_filter([
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
