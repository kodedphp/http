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

use Exception;
use Generator;
use Psr\Http\Message\StreamInterface;
use ReflectionFunction;
use RuntimeException;


class CallableStream implements StreamInterface
{
    /** @var callable */
    private $callable;

    /** @var int Current position of the pointer in the buffer */
    private $position = 0;

    /** @var bool If callable is Generator instance */
    private $isGenerator = false;

    public function __construct(callable $callable)
    {
        $this->callable    = $callable;
        $this->isGenerator = (new ReflectionFunction($this->callable))->isGenerator();
    }

    public function __destruct()
    {
        $this->close();
    }

    public function __toString(): string
    {
        try {
            return $this->getContents();
        } catch (RuntimeException $e) {
            return '';
        }
    }

    public function close(): void
    {
        $this->detach();
    }

    public function detach()
    {
        $this->callable = null;
        $this->position = 0;
    }

    public function getSize(): ?int
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
        $content = '';

        if (null === $this->callable) {
            return $content;
        }

        try {
            foreach ($this->reader($length) as $chunk) {
                $content        .= $chunk;
                $this->position += mb_strlen($chunk);
            }
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        } finally {
            $this->callable = null;
        }

        return $content;
    }

    public function getContents(): string
    {
        return $this->read(65536); // 64KB
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

    /**
     * @param int $length
     *
     * @return Generator
     * @throws RuntimeException
     */
    private function reader(int $length): Generator
    {
        if ($this->isGenerator) {
            yield from ($this->callable)();
        } elseif ($resource = fopen('php://temp', 'r+')) {
            if (false === @fwrite($resource, ($this->callable)())) {
                throw new RuntimeException('Cannot write to stream');
            }

            fseek($resource, 0);
            while (false === feof($resource)) {
                yield fread($resource, $length);
            }
            fclose($resource);
        }
    }
}
