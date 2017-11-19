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
use RuntimeException;
use Throwable;

class Stream implements StreamInterface {

    protected const MODES = [
        'w+'  => 1,
        'r+'  => 1,
        'x+'  => 1,
        'c+'  => 1,
        'a+'  => 1,
        'w+b' => 1,
        'r+b' => 1,
        'x+b' => 1,
        'c+b' => 1,
        'w+t' => 1,
        'r+t' => 1,
        'x+t' => 1,
        'c+t' => 1
    ];

    /** @var resource The underlying stream resource */
    protected $stream;

    protected $mode     = 0;
    protected $seekable = false;

    public function __construct($stream) {
        if (false === is_resource($stream) || 'stream' !== get_resource_type($stream)) {
            throw new RuntimeException('The provided resource is not a valid stream resource');
        }

        $metadata       = stream_get_meta_data($stream);
        $this->mode     = $metadata['mode'] ?? $this->mode;
        $this->seekable = $metadata['seekable'];
        $this->stream   = $stream;
    }

    public function __destruct() {
        $this->close();
    }

    public function __toString() {
        try {
            $this->seek(0);

            return $this->getContents();
        } catch (Throwable $e) {
            return '';
        }
    }

    public function close() {
        if ($this->stream) {
            fclose($this->stream);
            $this->detach();
        }
    }

    public function detach() {
        if (empty($this->stream)) {
            return null;
        }

        $resource       = $this->stream;
        $this->stream   = null;
        $this->mode     = 0;
        $this->seekable = false;

        return $resource;
    }

    public function getSize(): ?int {
        if (empty($this->stream)) {
            return null;
        }

        return fstat($this->stream)['size'] ?? null;
    }

    public function tell(): int {
        if (false === $position = ftell($this->stream)) {
            throw new RuntimeException('Failed to find the position of the file pointer');
        }

        return $position;
    }

    public function eof(): bool {
        return feof($this->stream);
    }

    public function seek($offset, $whence = SEEK_SET): void {
        if (false === $this->seekable) {
            throw new RuntimeException('The stream is not seekable');
        }

        if (0 !== fseek($this->stream, $offset, $whence)) {
            throw new RuntimeException('Failed to seek to file pointer');
        }
    }

    public function rewind(): void {
        $this->seek(0);
    }

    public function write($string): int {
        if (false === $this->isWritable()) {
            throw new RuntimeException('The stream is not writable');
        }

        if (false === $bytes = fwrite($this->stream, $string)) {
            throw new RuntimeException('Failed to write data to the stream');
        }

        return $bytes;
    }

    public function read($length): string {
        if (false === $this->isReadable()) {
            throw new RuntimeException('The stream is not readable');
        }

        if (empty($length)) {
            return '';
        }

        if (false === $data = fread($this->stream, $length)) {
            throw new RuntimeException('Failed to read the data from stream');
        }

        return $data;
    }

    public function getContents(): string {
        if (false === $content = stream_get_contents($this->stream)) {
            throw new RuntimeException('Unable to read the stream content');
        }

        return $content;
    }

    public function getMetadata($key = null) {
        $metadata = stream_get_meta_data($this->stream);

        if (null === $key) {
            return $metadata;
        }

        return $metadata[$key] ?? null;
    }

    public function isSeekable(): bool {
        return $this->seekable;
    }

    public function isReadable(): bool {
        return isset((self::MODES + ['r' => 1, 'rb' => 1, 'rt' => 1])[$this->mode]);
    }

    public function isWritable(): bool {
        return isset((self::MODES + ['w' => 1, 'rw' => 1, 'a' => 1, 'wb' => 1])[$this->mode]);
    }
}
