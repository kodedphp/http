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
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/**
 * @param null|callable|StreamInterface|object $resource A gypsy wannabe argument
 * @param string                        $mode
 *
 * @return StreamInterface
 */
function create_stream($resource, string $mode = 'r+'): StreamInterface
{
    if (is_scalar($resource) or null === $resource) {
        $stream = fopen('php://temp', $mode);
        fwrite($stream, $resource);
        fseek($stream, 0);

        return new Stream($stream);
    }

    if ($resource instanceof StreamInterface) {
        return $resource;
    }

    $type = gettype($resource);

    if ('resource' === $type) {
        return new Stream($resource);
    }

    if ('object' === $type and method_exists($resource, '__toString')) {
        return create_stream((string)$resource);
    }

    if (is_callable($resource)) {
        return new CallableStream($resource);
    }

    throw new InvalidArgumentException("The provided resource type {$type} is not valid");
}

/**
 * @param StreamInterface $source
 * @param StreamInterface $destination
 *
 * @return int The total count of bytes copied
 * @throws RuntimeException on failure
 */
function stream_copy(StreamInterface $source, StreamInterface $destination): int
{
    $bytes = 0;
    while (false === $source->eof()) {
        $bytes += $destination->write($source->read(8192));
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
    $stream ->rewind();

    while (false === $stream->eof()) {
        $content .= $stream->read(1048576); // 1MB
    }

    return $content;
}

/**
 * Transforms the (_FILES) array to much desired array structure.
 * Deals with any nested level.
 *
 * @param array $files Typically the $_FILES array
 *
 * @return array Normalized _FILES array to sane format
 */
function normalize_files_array(array $files): array
{
    $sane = function($files, $file = [], $path = []) use (&$sane) {
        foreach ($files as $k => $v) {
            $_ = $path;
            $_[] = $k;

            if (is_array($v)) {
                $file = $sane($v, $file, $_);
            } else {
                $next = array_splice($_, 1, 1);
                $_    = array_merge($_, $next);

                $copy = &$file;
                foreach ($_ as $k) {
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
        } elseif (is_array($file) and isset($file['tmp_name'])) {
            $files[$index] = new UploadedFile($file['tmp_name'], $file);
        } elseif (is_array($file)) {
            $files[$index] = build_files_array($file);
            continue;
        } else {
            throw new InvalidArgumentException('Failed to process the uploaded files. Invalid file structure provided');
        }
    }

    return $files;
}