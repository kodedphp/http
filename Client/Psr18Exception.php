<?php

namespace Koded\Http\Client;

use Psr\Http\Client\{NetworkExceptionInterface, RequestExceptionInterface};
use Psr\Http\Message\RequestInterface;

class Psr18Exception extends \Exception implements RequestExceptionInterface, NetworkExceptionInterface
{
    private RequestInterface $request;

    public function __construct(
        string $message,
        int $code,
        RequestInterface $request,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
