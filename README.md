Koded - HTTP Library
====================

[![Latest Stable Version](https://img.shields.io/packagist/v/koded/http.svg)](https://packagist.org/packages/koded/http)
[![Build Status](https://travis-ci.org/kodedphp/http.svg?branch=master)](https://travis-ci.org/kodedphp/http)
[![Codacy Badge](https://api.codacy.com/project/badge/Coverage/77246045a5c440ddb0efb195b48362ef)](https://www.codacy.com/app/kodeart/http)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/77246045a5c440ddb0efb195b48362ef)](https://www.codacy.com/app/kodeart/http)
[![Packagist Downloads](https://img.shields.io/packagist/dt/koded/http.svg)](https://packagist.org/packages/koded/http)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.1-8892BF.svg)](https://php.net/)
[![Software license](https://img.shields.io/badge/License-BSD%203--Clause-blue.svg)](LICENSE)


Koded HTTP library implements PSR-7 (HTTP message).

To provide you with more useful methods, the request and response instances
are extended with [additional interfaces](#interfaces) that you may use in your projects.


ServerRequest
-------------

```php
class ServerRequest extends ClientRequest implements Request {}
```

This object represents the incoming server-side HTTP request.

![](diagrams/server-request.png)


ClientRequest
-------------

```php
class ClientRequest implements RequestInterface, JsonSerializable {}
```

This object is a representation of an outgoing client-side HTTP request.

![](diagrams/client-request.png)


ServerResponse
--------------

```php
class ServerResponse implements Response, JsonSerializable {}
```

This object represents the outgoing server-side response.

![](diagrams/server-response.png)


UploadedFile
------------

This value object represents a file uploaded through an HTTP request.


Factories
---------

```php
$clientRequest = (new RequestFactory)->createRequest('GET', '/');
$serverRequest = (new ServerRequestFactory)->createServerRequest('GET', '/');

$response = (new ResponseFactory)->createResponse(201);

$stream = (new StreamFactory)->createStream('Hello there');
$stream = (new StreamFactory)->createStreamFromFile('file.name', '+w');
$stream = (new StreamFactory)->createStreamFromResource($resource);

$url = (new UriFactory)->createUri('/');

$uploadFile = (new UploadedFileFactory)->createUploadedFile($stream);
```

HTTP clients
============

There are 2 implementations for `ClientRequest` interface
- PHP
- curl

To create instances of HTTP clients, use the `Koded\Http\Client\ClientFactory` class

```php
<?php

use Koded\Http\Client\ClientFactory;

$http = new ClientFactory(ClientFactory::CURL); // or ClientFactory::PHP

$http->get('/', $headers);
$http->post('/', $body, $headers);
$http->put('/', $body, $headers);
$http->patch('/', $body, $headers);
$http->delete('/', $headers);
$http->head('/', $headers);
```

`$headers` are optional.


Additional interfaces
=====================
![Additional interfaces](./diagrams/interfaces.png)

- `Koded\Http\Request`
- `Koded\Http\Response`

These two may be useful in your project as they provide additional 
methods to manipulate the request/response objects state.

### Request
- `getPath(): string`
- `getBaseUri(): string`
- `withAttributes(array $attributes): Request`
- `isSecure(): bool`
- `isSafeMethod(): bool`
- `isXHR(): bool`

### Response
- `getContentType(): string`


HttpInputValidator
------------------
// TODO

Other interfaces in this package are mostly for internal use.


License
-------

The code is distributed under the terms of [The 3-Clause BSD license](LICENSE).
