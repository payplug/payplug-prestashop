<?php

namespace PayPlug\tests\utilities\validators\AccountValidator;

use PayPlug\src\utilities\validators\accountValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group account_validator
 *
 * @runTestsInSeparateProcesses
 */
class isApiKeyTest extends TestCase
{
    protected $accountValidator;

    protected function setUp()
    {
        $this->accountValidator = new AccountValidator();
    }

    public function invalidTypeApiKeyDataProvider()
    {
        yield [42, 'Invalid argument given, $api_key must be a non empty string'];
        yield [['key' => 'value'], 'Invalid argument given, $api_key must be a non empty string'];
        yield [false, 'Invalid argument given, $api_key must be a non empty string'];
        yield ['', 'Invalid argument given, $api_key must be a non empty string'];
    }

    public function invalidDataApiKeyDataProvider()
    {
        yield ['sk_live_azertyuiopqsdfghjklmw', 'Invalid argument given, $api_key is not allowed']; // 29 char
        yield ['sk_liv_azertyuiopqsdfghjklmwxc', 'Invalid argument given, $api_key is not allowed']; // sk_liv invalid
        yield ['azerty_sk_live_uiopqsdfghjklmw', 'Invalid argument given, $api_key is not allowed']; // sk_live must be at the beginning
    }

    public function validApiKeyDataProvider()
    {
        yield ['sk_live_azertyuiopqsdfghjklmwx', '']; // Success
    }

    /**
     * @dataProvider invalidTypeApiKeyDataProvider
     *
     * @param mixed $apiKey
     * @param mixed $errorMsg
     */
    public function testWithInvalidTypeApiKeyDataProvider($apiKey, $errorMsg)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => $errorMsg,
            ],
            $this->accountValidator->isApiKey($apiKey)
        );
    }

    /**
     * @dataProvider invalidDataApiKeyDataProvider
     *
     * @param mixed $apiKey
     * @param mixed $errorMsg
     */
    public function testWithInvalidDataApiKeyDataProvider($apiKey, $errorMsg)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => $errorMsg,
            ],
            $this->accountValidator->isApiKey($apiKey)
        );
    }

    /**
     * @dataProvider validApiKeyDataProvider
     *
     * @param mixed $apiKey
     * @param mixed $msg
     */
    public function testWithValidApiKeyDataProvider($apiKey, $msg)
    {
        $this->assertSame(
            [
                'result' => true,
                'message' => $msg,
            ],
            $this->accountValidator->isApiKey($apiKey)
        );
    }
}
