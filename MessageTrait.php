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
use Psr\Http\Message\StreamInterface;

trait MessageTrait
{

    /** @var string HTTP protocol */
    protected $protocolVersion = '1.1';

    /** @var StreamInterface */
    protected $stream;

    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version)
    {
        if (false === in_array($version, ['1.0', '1.1', '2'], true)) {
            throw new InvalidArgumentException('Unsupported HTTP protocol version ' . $version);
        }

        $instance = clone $this;

        $instance->protocolVersion = $version;

        return $instance;
    }

    public function getBody(): ?StreamInterface
    {
        return $this->stream;
    }

    public function withBody(StreamInterface $body)
    {
        $instance = clone $this;

        $instance->stream = $body;

        return $instance;
    }
}
