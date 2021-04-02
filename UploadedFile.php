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

use Koded\Exceptions\KodedException;
use Psr\Http\Message\{StreamInterface, UploadedFileInterface};
use function Koded\Stdlib\randomstring;


class UploadedFile implements UploadedFileInterface
{
    private ?string $file;
    private ?string $name;
    private ?string $type;
    private ?int $size;
    private int  $error;
    private bool $moved = false;

    public function __construct(array $uploadedFile)
    {
        $this->size  = $uploadedFile['size'] ?? null;
        $this->file  = $uploadedFile['tmp_name'] ?? null;
        $this->name  = $uploadedFile['name'] ?? randomstring(9);
        $this->error = (int)($uploadedFile['error'] ?? \UPLOAD_ERR_OK);

        // Create a file out of the stream
        if ($this->file instanceof StreamInterface) {
            $file = \sys_get_temp_dir() . '/' . $this->name;
            \file_put_contents($file, $this->file->getContents());
            $this->file = $file;
        } elseif (false === \is_string($this->file)) {
            throw UploadedFileException::fileNotSupported();
        } elseif (0 === \strlen($this->file)) {
            throw UploadedFileException::filenameCannotBeEmpty();
        }
        // Never trust the provided mime type
        $this->type = $this->getClientMediaType();
    }

    public function getStream(): StreamInterface
    {
        if ($this->moved) {
            throw UploadedFileException::streamNotAvailable();
        }
        return new FileStream($this->file, 'w+b');
    }

    public function moveTo($targetPath)
    {
        $this->assertUploadError();
        $this->assertTargetPath($targetPath);
        // @codeCoverageIgnoreStart
        try {
            $this->moved = ('cli' === \php_sapi_name())
                ? \rename($this->file, $targetPath)
                : \move_uploaded_file($this->file, $targetPath);

            @\unlink($this->file);
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage());
        }
        // @codeCoverageIgnoreEnd
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->name;
    }

    public function getClientMediaType(): ?string
    {
        try {
            return (new \finfo(\FILEINFO_MIME_TYPE))->file($this->file);
        } catch (\Throwable) {
            return $this->type;
        }
    }

    private function assertUploadError(): void
    {
        if ($this->error !== \UPLOAD_ERR_OK) {
            throw new UploadedFileException($this->error);
        }
    }

    private function assertTargetPath($targetPath): void
    {
        if ($this->moved) {
            throw UploadedFileException::fileAlreadyMoved();
        }
        if (false === \is_string($targetPath) || 0 === \mb_strlen($targetPath)) {
            throw UploadedFileException::targetPathIsInvalid();
        }
        if (false === \is_dir($dirname = \dirname($targetPath))) {
            @\mkdir($dirname, 0777, true);
        }
    }
}


class UploadedFileException extends KodedException
{
    protected $messages = [
        \UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the "upload_max_filesize" directive in php.ini',
        \UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the "MAX_FILE_SIZE" directive that was specified in the HTML form',
        \UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded',
        \UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        \UPLOAD_ERR_NO_TMP_DIR => 'The temporary directory to write to is missing',
        \UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        \UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload',
    ];

    public static function streamNotAvailable(): \RuntimeException
    {
        return new \RuntimeException('Stream is not available, because the file was previously moved');
    }

    public static function targetPathIsInvalid(): \InvalidArgumentException
    {
        return new \InvalidArgumentException('The provided path for moveTo operation is not valid');
    }

    public static function fileAlreadyMoved(): \RuntimeException
    {
        return new \RuntimeException('File is not available, because it was previously moved');
    }

    public static function fileNotSupported(): \InvalidArgumentException
    {
        return new \InvalidArgumentException('The uploaded file is not supported');
    }

    public static function filenameCannotBeEmpty(): \InvalidArgumentException
    {
        return new \InvalidArgumentException('Filename cannot be empty');
    }
}
