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

use Psr\Http\Message\UploadedFileInterface;


trait FilesTrait
{
    /** @var UploadedFileInterface[] */
    protected array $uploadedFiles = [];

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): static
    {
        $instance                = clone $this;
        $instance->uploadedFiles = $this->parseUploadedFiles($uploadedFiles);
        return $instance;
    }

    /**
     * Transforms the confusing _FILES array into a list
     * with UploadedFileInterface instances.
     *
     * @param array $uploadedFiles
     *
     * @return UploadedFileInterface[]
     */
    protected function parseUploadedFiles(array $uploadedFiles): array
    {
        return build_files_array(normalize_files_array($uploadedFiles));
    }
}
