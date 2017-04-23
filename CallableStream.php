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

use Psr\Http\Message\StreamInterface;
use ReflectionFunction;
use RuntimeException;

class CallableStream implements StreamInterface
{

    /** @var callable */
    private $callable;

    /** @var int Current position of the pointer in the buffer */
    private $position = 0;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function __toString(): string
    {
        return $this->getContents();
    }

    public function close()
    {
        $this->detach();
    }

    public function detach()
    {
        $this->callable = null;
        $this->position = 0;
    }

    public function getSize()
    {
        return null;
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return null === $this->callable;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        throw new RuntimeException('Cannot seek in CallableStream');
    }

    public function rewind(): void
    {
        throw new RuntimeException('Cannot rewind the CallableStream');
    }

    public function write($string): int
    {
        throw new RuntimeException('Cannot write to CallableStream');
    }

    public function read($length): string
    {
        $contents = '';

        if (null === $this->callable) {
            return $contents;
        }

        foreach ($this->generator($length) as $chunk) {
            $contents .= $chunk;

            $this->position = mb_strlen($contents);
        }

        $this->callable = null;

        return $contents;
    }

    public function getContents(): string
    {
        return $this->read(1048576); // 1MB
    }

    public function getMetadata($key = null)
    {
        return $key ? null : [];
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function isReadable(): bool
    {
        return true;
    }

    private function generator(int $length)
    {
        if ((new ReflectionFunction($this->callable))->isGenerator()) {
            yield from ($this->callable)();
        } else {
            $resource = fopen('php://temp', 'r+');
            @fwrite($resource, call_user_func($this->callable));
            fseek($resource, 0);

            while (!feof($resource)) {
                yield fread($resource, $length);
            }

            fclose($resource);
        }
    }
}
