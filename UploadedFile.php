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
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
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
        $this->file  = $uploadedFile['tmp_name'] ?? null;
        $this->name  = $uploadedFile['name'] ?? null;
        $this->type  = $uploadedFile['type'] ?? null;
        $this->size  = $uploadedFile['size'] ?? null;
        $this->error = $uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE;

        $this->file && $this->stream = create_stream($this->file);
    }

    public function getStream(): StreamInterface
    {
        $this->assertMoved();

        if (!$this->stream instanceof StreamInterface) {
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
            throw new RuntimeException('Failed to get the stream because it was previously moved');
        }
    }
}

class UploadedFileException extends KodedException
{
    protected $messages = [
        UPLOAD_ERR_INI_SIZE   => 'Ini size',
        UPLOAD_ERR_FORM_SIZE  => 'Form size',
        UPLOAD_ERR_PARTIAL    => 'Partial',
        UPLOAD_ERR_NO_FILE    => 'No file',
        UPLOAD_ERR_NO_TMP_DIR => 'No tmp directory',
        UPLOAD_ERR_CANT_WRITE => "Can't write",
        UPLOAD_ERR_EXTENSION  => 'Extension',
    ];
}
