<?php

namespace Tests\Koded\Http;

use Koded\Http\Interfaces\HttpInputValidator;
use Koded\Http\ServerRequest;
use Koded\Http\StatusCode;
use Koded\Stdlib\Data;
use PHPUnit\Framework\TestCase;

class HttpInputValidatorTest extends TestCase
{
    public function test_success_validate_with_empty_body()
    {
        $request = new ServerRequest;
        $response = $request->validate(new TestSuccessValidator, $input);

        $this->assertSame(StatusCode::BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('{"validate":"Nothing to validate","code":400}', (string)$response->getBody());

        $this->assertInstanceOf(Data::class, $input);
        $this->assertCount(0, $input);
    }

    public function test_success_validate_with_body()
    {
        $_POST = ['key' => 'value'];

        $request = new ServerRequest;
        $response = $request->validate(new TestSuccessValidator, $input);

        $this->assertNull($response);
        $this->assertEquals($_POST, $input->toArray());
    }

    public function test_failure_validate()
    {
        $_POST = ['key' => 'value'];

        $request = new ServerRequest;
        $response = $request->validate(new TestFailureValidator);

        $this->assertSame(StatusCode::BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('{"message":"This is the error message","status":400}', (string)$response->getBody());
    }

    public function test_failure_validate_response_code()
    {
        $_POST = ['key' => 'value'];

        $request = new ServerRequest;
        $response = $request->validate(new TestFailureValidatorWithStatusCode);

        $this->assertSame(StatusCode::UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertSame('{"text":"Cannot proceed","status":422}', (string)$response->getBody());
    }

    protected function tearDown(): void
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
            'status' => StatusCode::UNPROCESSABLE_ENTITY,
        ];
    }
}