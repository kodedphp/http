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
use Koded\Exceptions\KodedException;
use Psr\Http\Message\{StreamInterface, UploadedFileInterface};
use RuntimeException;
use Throwable;

class UploadedFile implements UploadedFileInterface
{

    /** @var string|null */
    private $file;

    /** @var string|null */
    private $name;

    /** @var string|null */
    private $type;

    /** @var int|null */
    private $size;

    /** @var int See UPLOAD_ERR_* constants */
    private $error = UPLOAD_ERR_OK;

    /** @var bool */
    private $moved = false;

    /** @var StreamInterface */
    private $stream;

    public function __construct(array $uploadedFile)
    {
        if ($this->file = $uploadedFile['tmp_name'] ?? null) {
            $this->stream = create_stream($this->file);
        }

        $this->name  = $uploadedFile['name'] ?? null;
        $this->type  = $uploadedFile['type'] ?? null;
        $this->size  = $uploadedFile['size'] ?? null;
        $this->error = $uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE;
    }

    public function getStream(): StreamInterface
    {
        $this->assertMoved();

        if (empty($this->stream)) {
            throw new RuntimeException('The stream is not available for the uploaded file');
        }

        return $this->stream;
    }

    public function moveTo($targetPath)
    {
        $this->assertUploadError();

        if ('' === trim($targetPath)) {
            throw new InvalidArgumentException('The provided path for moveTo operation is not valid');
        }

        try {
            stream_copy($this->getStream(), create_stream($targetPath, 'w'));
            $destination = rtrim($targetPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->name;

            $this->moved = ('cli' === php_sapi_name())
                ? rename($this->file, $destination) : move_uploaded_file($this->file, $destination);

            $this->stream = null;
            @unlink($this->file);
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error ?? UPLOAD_ERR_NO_FILE;
    }

    public function getClientFilename(): ?string
    {
        return $this->name;
    }

    public function getClientMediaType(): ?string
    {
        try {
            return (new \finfo(FILEINFO_MIME_TYPE))->file($this->file);
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            return $this->type;
        }
        // @codeCoverageIgnoreEnd

    }

    /**
     * @throws UploadedFileException If the resource upload failed
     */
    private function assertUploadError(): void
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new UploadedFileException($this->error, [':file' => $this->file]);
        }
    }

    /**
     * @throws RuntimeException If the moveTo() method has been called previously
     */
    private function assertMoved(): void
    {
        if ($this->moved) {
            throw new RuntimeException('Failed to get the file stream, because it was previously moved');
        }
    }
}

class UploadedFileException extends KodedException
{
    protected $messages = [
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the "upload_max_filesize" directive in php.ini',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the "MAX_FILE_SIZE" directive that was specified in the HTML form',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'The temporary directory to write to is missing',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload',
    ];
}
