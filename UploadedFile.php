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

    public function __construct($resource, array $uploadedFile)
    {
        $this->file  = $uploadedFile['tmp_name'] ?? null;
        $this->name  = $uploadedFile['name'] ?? null;
        $this->type  = $uploadedFile['type'] ?? null;
        $this->size  = $uploadedFile['size'] ?? null;
        $this->error = $uploadedFile['error'] ?? UPLOAD_ERR_OK;

        if ($this->error === UPLOAD_ERR_OK) {
            $this->createStream($resource);
        }
    }

    /**
     * Retrieve a stream representing the uploaded file.
     *
     * This method MUST return a StreamInterface instance, representing the
     * uploaded file. The purpose of this method is to allow utilizing native PHP
     * stream functionality to manipulate the file upload, such as
     * stream_copy_to_stream() (though the result will need to be decorated in a
     * native PHP stream wrapper to work with such functions).
     *
     * If the moveTo() method has been called previously, this method MUST raise
     * an exception.
     *
     * @return StreamInterface Stream representation of the uploaded file.
     * @throws RuntimeException in cases when no stream is available or can be
     *     created.
     */
    public function getStream(): StreamInterface
    {
        $this->assertMoved();
        $this->assertError();

        return $this->stream;
    }

    public function moveTo($targetPath)
    {
        if (empty($targetPath)) {
            // TODO this wont allow path with name "0"
            throw new InvalidArgumentException('The provided path for moveTo operation cannot be empty');
        }

        if (null === $this->file) {
            $this->moved = stream_copy($this->getStream(), new FileStream($targetPath, 'w')) > 0;
        } else {
            $this->assertMoved();
            $this->assertError();

            $this->moved = ('cli' === php_sapi_name())
                ? rename($this->file, $targetPath)
                : move_uploaded_file($this->file, $targetPath);
        }

        if (false === $this->moved) {
            throw new RuntimeException('Failed to move the file to the requested location');
        }
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

    /**
     * Retrieve the media type sent by the client.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious media type with the intention to corrupt or hack your
     * application.
     *
     * Implementations SHOULD return the value stored in the "type" key of
     * the file in the $_FILES array.
     *
     * @return string|null The media type sent by the client or null if none
     *     was provided.
     */
    public function getClientMediaType(): ?string
    {
        return $this->type;
    }

    /**
     * @throws UploadedFileException If the resource upload failed
     */
    private function assertError(): void
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

    /**
     * @param resource|StreamInterface|string $resource A gypsy argument for all the resource things
     *
     * @return UploadedFile
     */
    private function createStream($resource): UploadedFile
    {
        if (is_string($resource)) {
            $this->stream = new FileStream($resource, 'r+');

            return $this;
        }

        if ($resource instanceof StreamInterface) {
            $this->stream = $resource;

            return $this;
        }

        if (is_resource($resource)) {
            $this->stream = create_stream($resource);

            return $this;
        }

        throw new InvalidArgumentException('Invalid resource provided for UploadedFile. ' .
            'Expected a file name, StreamInterface instance, or resource');
    }
}

class UploadedFileException extends KodedException
{
    protected $messages = [
        UPLOAD_ERR_INI_SIZE   => 'Init size',
        UPLOAD_ERR_FORM_SIZE  => 'Form size',
        UPLOAD_ERR_PARTIAL    => 'Partial',
        UPLOAD_ERR_NO_FILE    => 'No file',
        UPLOAD_ERR_NO_TMP_DIR => 'No tmp directory',
        UPLOAD_ERR_CANT_WRITE => "Can't write",
        UPLOAD_ERR_EXTENSION  => 'Extension',
    ];
}
