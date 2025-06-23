<?php

namespace Tests\Koded\Http;

use Koded\Http\{HTTPBadRequest, HTTPError, HTTPMethodNotAllowed};
use PHPUnit\Framework\TestCase;

class HTTPErrorTest extends TestCase
{
    public function test_default_title_detail_and_message()
    {
        $exception = new HTTPError(409);

        $this->assertSame('Conflict', $exception->getTitle());
        $this->assertNotEmpty($exception->getDetail());
        $this->assertNotEmpty($exception->getMessage());

        $this->assertSame(
            $exception->getDetail(),
            $exception->getMessage(),
            'Default message and detail are the same'
        );

        $this->assertSame(
            'The request could not be completed due to a conflict with the current state of the target resource',
            $exception->getDetail()
        );
    }

    public function test_extensions()
    {
        $exception = (new HTTPBadRequest)
            ->setMember('accounts', [
                0 => 'account 1',
                1 => 'account 2'
            ])
            ->setMember('balance', 30.0);

        $expectedXml = <<<'XMLDOC'
<?xml version="1.0" encoding="UTF-8"?>
<problem xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <status type="xsd:positiveInteger">400</status>
    <detail>The response means that server cannot understand the request due to invalid syntax</detail>
    <title>Bad Request</title>
    <type type="xsd:anyURI">https://httpstatuses.com/400</type>
    <accounts>account 1</accounts>
    <accounts>account 2</accounts>
    <balance type="xsd:float">30</balance>
</problem>
XMLDOC;

        $this->assertXmlStringEqualsXmlString($expectedXml, $exception->toXml());
    }
}
