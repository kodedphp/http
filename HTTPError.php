<?php

namespace Koded\Http;

use Koded\Http\Interfaces\HttpStatus;
use RuntimeException;
use Throwable;
use function array_filter;
use function Koded\Stdlib\json_serialize;
use function Koded\Stdlib\xml_serialize;
use function rawurldecode;

interface HTTPException extends Throwable
{
    public function getStatusCode(): int;

    public function getTitle(): string;

    public function getType(): string;

    public function getDetail(): string;

    public function getInstance(): string;

    public function getHeaders(): iterable;

    public function setInstance(string $value): static;

    public function setMember(string $name, mixed $value): static;

    public function toJson(): string;

    public function toXml(): string;

    public function toArray(): array;
}

/**
 * Represents a generic HTTP error, that
 * follows the RFC-9457 (and RFC-7807) standard.
 *
 * Raise an instance of subclass of `HTTPError` to have Koded return
 * a formatted error response and appropriate HTTP status code to
 * the client when something goes wrong. JSON and XML media types are
 * supported by default.
 *
 * NOTE:
 *  if you wish to return custom error messages, you can create
 *  your own HTTPError subclass and register it with the error
 *  handler method to convert it into the desired HTTP response.
 *
 * @link https://datatracker.ietf.org/doc/html/rfc9457
 */
class HTTPError extends RuntimeException implements HTTPException
{
    /**
     * Extension members for problem type definitions may extend the
     * problem details object with additional information. Clients
     * consuming problem MUST ignore any extensions that they don't
     * recognize, allowing problem types to evolve and include
     * additional information in the future.
     *
     * @var array
     */
    protected array $members = [];

    /**
     * HTTPError constructor.
     *
     * @param int             $status   HTTP status code
     * @param string          $title    Error title to send to the client. If not provided, defaults to status line
     * @param string          $detail   Human-friendly description of the error, along with a helpful suggestion or two
     * @param string          $instance A URI reference that identifies the specific occurrence of the problem.
     * @param string          $type     A URI reference that identifies the problem type and points to a human-readable documentation
     * @param array|null      $headers  Extra headers to add to the response
     * @param Throwable|null $previous  The previous Throwable, if any
     */
    public function __construct(
        int              $status,
        protected string $title = '',
        protected string $detail = '',
        protected string $instance = '',
        protected string $type = '',
        protected ?array $headers = [],
        ?Throwable       $previous = null)
    {
        $this->code = $status;
        $this->code = static::status($this);
        $this->message = $detail ?: StatusCode::description($this->code);
        [
            'title'    => $this->title,
            'detail'   => $this->detail,
            'instance' => $this->instance,
            'type'     => $this->type,
        ] = $this->toArray();
        parent::__construct($this->message, $this->code, $previous);
    }

    public static function status(
        Throwable $ex,
        int $prefer = HttpStatus::I_AM_TEAPOT): int
    {
        $code = $ex->getCode();
        return ($code < 100 || $code > 599) ? $prefer : $code;
    }

    public function getStatusCode(): int
    {
        return $this->code;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDetail(): string
    {
        return $this->detail;
    }

    public function getInstance(): string
    {
        return $this->instance;
    }

    public function getHeaders(): iterable
    {
        return $this->headers ?? [];
    }

    public function setInstance(string $value): static
    {
        $this->instance = $value;
        return $this;
    }

    public function setMember(string $name, mixed $value): static
    {
        $this->members[$name] = $value;
        return $this;
    }

    public function toJson(): string
    {
        return rawurldecode(json_serialize(array_filter($this->toArray())));
    }

    public function toXml(): string
    {
        $data = array_filter($this->toArray());
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = $v;
            } else if ($k === 'status') {
                $data[$k] = [
                    '@type' => 'xsd:positiveInteger',
                    '#'     => $v
                ];
            } else if ($k === 'instance' || $k === 'type') {
                $data[$k] = [
                    '@type' => 'xsd:anyURI',
                    '#'     => $v
                ];
            }
        }
        return rawurldecode(xml_serialize('problem', array_filter($data)));
        //return rawurldecode(xml_serialize('problem', array_filter($this->toArray())));
    }

    /**
     * @return array{status: int, instance: string, detail: string, title: string, type: string}
     */
    public function toArray(): array
    {
        $status = static::status($this);
        return [
            'status'   => $status,
            'instance' => $this->instance,
            'detail'   => $this->detail ?: $this->message,
            'title'    => $this->title ?: HttpStatus::CODE[$this->code],
            'type'     => $this->type ?: "https://httpstatuses.com/$status",
        ] + $this->members;
    }

    /**
     * Implements the Stringable interface.
     * Useful when converting the instance as \Psr\Http\Message\StreamInterface,
     * or typecasting it as a string.
     *
     * @return string JSON representation of the HTTPError.
     * @implements \Stringable
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * @internal
     */
    public function __serialize(): array
    {
        return $this->toArray() + [
            'members' => $this->members,
            'headers' => $this->headers,
        ];
    }

    /**
     * @internal
     */
    public function __unserialize(array $serialized): void
    {
        [
            'status'   => $this->code,
            'detail'   => $this->detail,
            'detail'   => $this->message, // copy message
            'title'    => $this->title,
            'instance' => $this->instance,
            'type'     => $this->type,
            'members'  => $this->members,
            'headers'  => $this->headers,
        ] = $serialized;
    }
}
