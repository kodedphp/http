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
use Psr\Http\Message\UploadedFileInterface;

trait FilesTrait
{

    protected $uploadedFiles = [];

    /**
     * Retrieve normalized file upload data.
     *
     * This method returns upload metadata in a normalized tree, with each leaf
     * an instance of Psr\Http\Message\UploadedFileInterface.
     *
     * These values MAY be prepared from $_FILES or the message body during
     * instantiation, or MAY be injected via withUploadedFiles().
     *
     * @return array An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param array $uploadedFiles An array tree of UploadedFileInterface instances.
     *
     * @return static
     * @throws InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles(array $uploadedFiles): array
    {
        $instance = clone $this;

        $instance->uploadedFiles = $this->parseUploadedFiles($uploadedFiles);

        return $instance->uploadedFiles;
    }

    /**
     * @TODO
     *
     * @param array $uploadedFiles
     *
     * @return UploadedFileInterface[]
     */
    protected function parseUploadedFiles(array $uploadedFiles): array
    {
        $uploadedFiles = normalize_files_array($uploadedFiles);

        return build_files_array($uploadedFiles);
    }
}
