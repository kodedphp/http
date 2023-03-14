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

use finfo;
use InvalidArgumentException;
use Koded\Exceptions\KodedException;
use RuntimeException;
use Psr\Http\Message\{StreamInterface, UploadedFileInterface};
use Throwable;
use function dirname;
use function file_put_contents;
use function get_debug_type;
use function is_dir;
use function is_string;
use function Koded\Stdlib\randomstring;
use function mb_strlen;
use function mkdir;
use function move_uploaded_file;
use function php_sapi_name;
use function rename;
use function sys_get_temp_dir;
use function unlink;
use const FILEINFO_MIME_TYPE;
use const UPLOAD_ERR_CANT_WRITE;
use const UPLOAD_ERR_EXTENSION;
use const UPLOAD_ERR_FORM_SIZE;
use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_NO_TMP_DIR;
use const UPLOAD_ERR_OK;
use const UPLOAD_ERR_PARTIAL;

class UploadedFile implements UploadedFileInterface
{
    private mixed $file;
    private mixed $name;
    private ?string $type;
    private ?int $size;
    private int  $error;
    private bool $moved = false;

    public function __construct(array $uploadedFile)
    {
        $this->file  = $uploadedFile['tmp_name'] ?? null;
        $this->name  = $uploadedFile['name'] ?? randomstring(9);
        $this->size  = $uploadedFile['size'] ?? null;
        $this->prepareFile();
        $this->type = $this->getClientMediaType();
        $this->error = (int)($uploadedFile['error'] ?? UPLOAD_ERR_OK);
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
            $this->moved = ('cli' === php_sapi_name())
                ? rename($this->file, $targetPath)
                : move_uploaded_file($this->file, $targetPath);

            @unlink($this->file);
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage());
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
            return @(new finfo(FILEINFO_MIME_TYPE))->file($this->file);
        } catch (Throwable) {
            return $this->type;
        }
    }

    private function assertUploadError(): void
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new UploadedFileException($this->error);
        }
    }

    private function assertTargetPath($targetPath): void
    {
        if ($this->moved) {
            throw UploadedFileException::fileAlreadyMoved();
        }
        if (false === is_string($targetPath) || 0 === mb_strlen($targetPath)) {
            throw UploadedFileException::targetPathIsInvalid();
        }
        if (false === is_dir($dirname = dirname($targetPath))) {
            @mkdir($dirname, 0777, true);
        }
    }

    private function prepareFile(): void
    {
        if ($this->file instanceof StreamInterface) {
            // Create a temporary file out of the stream object
            $this->size = $this->file->getSize();
            $file = sys_get_temp_dir() . '/' . $this->name;
            file_put_contents($file, $this->file->getContents());
            $this->file = $file;
            return;
        }
        if (false === is_string($this->file)) {
            throw UploadedFileException::fileNotSupported($this->file);
        }
        if (0 === mb_strlen($this->file)) {
            throw UploadedFileException::filenameCannotBeEmpty();
        }
    }
}


class UploadedFileException extends KodedException
{
    protected array $messages = [
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the "upload_max_filesize" directive in php.ini',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the "MAX_FILE_SIZE" directive that was specified in the HTML form',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'The temporary directory to write to is missing',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload',
    ];

    public static function streamNotAvailable(): RuntimeException
    {
        return new RuntimeException('Stream is not available, because the file was previously moved');
    }

    public static function targetPathIsInvalid(): InvalidArgumentException
    {
        return new InvalidArgumentException('The provided path for moveTo operation is not valid');
    }

    public static function fileAlreadyMoved(): RuntimeException
    {
        return new RuntimeException('File is not available, because it was previously moved');
    }

    public static function fileNotSupported(mixed $file): InvalidArgumentException
    {
        return new InvalidArgumentException(sprintf(
            'The uploaded file is not supported, expected string, %s given', get_debug_type($file)
        ));
    }

    public static function filenameCannotBeEmpty(): InvalidArgumentException
    {
        return new InvalidArgumentException('Filename cannot be empty');
    }
}
