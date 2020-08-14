<?php

namespace Koded\Http;

/*
 * This file is part of the Koded package.
 *
 * (c) Mihail Binev <mihail@kodeart.com>
 *
 * Please view the LICENSE distributed with this source code
 * for the full copyright and license information.
 *
 */

use InvalidArgumentException;
use Psr\Http\Message\{StreamInterface, UploadedFileInterface};
use RuntimeException;

/**
 * @param null|callable|StreamInterface|object|resource $resource A gypsy wannabe argument
 * @param string                                        $mode
 *
 * @return StreamInterface
 */
function create_stream($resource, string $mode = 'r+'): StreamInterface
{
    if (null === $resource || is_string($resource)) {
        $stream = fopen('php://temp', $mode);
        fwrite($stream, $resource);
        fseek($stream, 0);
        return new Stream($stream);
    }

    if ($resource instanceof StreamInterface) {
        return $resource;
    }
    if (is_callable($resource)) {
        return new CallableStream($resource);
    }
    $type = gettype($resource);
    if ('resource' === $type) {
        return new Stream($resource);
    }
    if ('object' === $type && method_exists($resource, '__toString')) {
        return create_stream((string)$resource);
    }
    throw new InvalidArgumentException('Failed to create a stream. '
        . 'Expected a file name, StreamInterface instance, or a resource. '
        . "Given {$type} type.");
}

/**
 * Copies the stream to another stream object.
 *
 * @param StreamInterface $source      The source stream object
 * @param StreamInterface $destination Destination stream object
 * @param int             [optional] $length Read up to $length bytes from the source stream.
 *                                     Fewer than $length bytes may be returned if underlying stream
 *                                     call returns fewer bytes
 *
 * @return int The total count of bytes copied
 */
function stream_copy(StreamInterface $source, StreamInterface $destination, int $length = 8192): int
{
    $bytes = 0;
    while (false === $source->eof()) {
        $bytes += $destination->write($source->read($length));
    }
    $destination->close();
    return $bytes;
}

/**
 * @param StreamInterface $stream
 *
 * @return string
 * @throws RuntimeException on failure
 */
function stream_to_string(StreamInterface $stream): string
{
    $content = '';
    $stream->rewind();
    while (false === $stream->eof()) {
        $content .= $stream->read(1048576); // 1MB
    }
    return $content;
}

/**
 * Transforms the array to much desired files array structure.
 * Deals with any nested level.
 *
 * @param array $files Typically the $_FILES array
 *
 * @return array A files array to a sane format
 */
function normalize_files_array(array $files): array
{
    $sane = function(array $files, array $file = [], array $path = []) use (&$sane) {
        foreach ($files as $k => $v) {
            $list   = $path;
            $list[] = $k;
            if (is_array($v)) {
                $file = $sane($v, $file, $list);
            } else {
                $next = array_splice($list, 1, 1);
                $copy = &$file;
                foreach (array_merge($list, $next) as $k) {
                    $copy = &$copy[$k];
                }
                $copy = $v;
            }
        }
        return $file;
    };

    return $sane($files);
}

/**
 * Preserves the array structure and replaces the
 * file description with instance of UploadedFile.
 *
 * @param array $files Normalized _FILES array
 *
 * @return array An array tree of UploadedFileInterface instances
 */
function build_files_array(array $files): array
{
    foreach ($files as $index => $file) {
        if ($file instanceof UploadedFileInterface) {
            $files[$index] = $file;
        } elseif (isset($file['tmp_name']) && is_array($file)) {
            $files[$index] = new UploadedFile($file);
        } elseif (is_array($file)) {
            $files[$index] = build_files_array($file);
            continue;
        } else {
            throw new InvalidArgumentException('Failed to process the uploaded files. Invalid file structure provided');
        }
    }
    return $files;
}
