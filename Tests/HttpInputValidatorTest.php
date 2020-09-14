<?php

namespace Koded\Http;

use Koded\Http\Interfaces\HttpInputValidator;
use Koded\Stdlib\Data;
use PHPUnit\Framework\TestCase;

class HttpInputValidatorTest extends TestCase
{

    public function test_success_validate_with_empty_body()
    {
        $request = new ServerRequest;
        $response = $request->validate(new TestSuccessValidator);

        $this->assertSame(StatusCode::BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('{"validate":"Nothing to validate","code":400}', (string)$response->getBody());
    }

    public function test_success_validate_with_body()
    {
        $_POST = ['key' => 'value'];

        $request = new ServerRequest;
        $response = $request->validate(new TestSuccessValidator);

        $this->assertNull($response);
    }

    public function test_failure_validate()
    {
        $_POST = ['key' => 'value'];

        $request = new ServerRequest;
        $response = $request->validate(new TestFailureValidator);

        $this->assertSame(StatusCode::BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('{"message":"This is the error message","code":400}', (string)$response->getBody());
    }

    public function test_failure_validate_response_code()
    {
        $_POST = ['key' => 'value'];

        $request = new ServerRequest;
        $response = $request->validate(new TestFailureValidatorWithStatusCode);

        $this->assertSame(StatusCode::UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertSame('{"text":"Cannot proceed","code":422}', (string)$response->getBody());
    }

    protected function tearDown()
    {
        $_POST = [];
    }
}

class TestSuccessValidator implements HttpInputValidator {

    public function validate(Data $input): array
    {
        return [];
    }
}

class TestFailureValidator implements HttpInputValidator {

    public function validate(Data $input): array
    {
        return [
            'message' => 'This is the error message',
        ];
    }
}

class TestFailureValidatorWithStatusCode implements HttpInputValidator {

    public function validate(Data $input): array
    {
        return [
            'text' => 'Cannot proceed',
            'code' => StatusCode::UNPROCESSABLE_ENTITY,
        ];
    }
}