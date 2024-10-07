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

    public function setUp()
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

    public function invalidDataCharacterApiKeyDataProvider()
    {
        yield ['sk-live-azerty', 'Invalid argument given, $api_key contained invalid characters'];
        yield ['sk/live/azerty', 'Invalid argument given, $api_key contained invalid characters'];
        yield ['"sk_live_azerty"', 'Invalid argument given, $api_key contained invalid characters'];
        yield ['<sk/live/azerty>', 'Invalid argument given, $api_key contained invalid characters'];
    }

    public function invalidDataApiKeyDataProvider()
    {
        yield ['live_azertyuiopqsdfghjklmwtyu', 'Invalid argument given, $api_key is not allowed'];
        yield ['azerty_sk_live_azertyuiopqsdfghjklmwtyu', 'Invalid argument given, $api_key is not allowed'];
        yield ['azerty_pk_live_azertyuiopqsdfghjklmwtyu', 'Invalid argument given, $api_key is not allowed'];
        yield ['azerty_sk_live_azertyuiopqsdfghjklmwtyu', 'Invalid argument given, $api_key is not allowed'];
        yield ['azerty_pk_live_azertyuiopqsdfghjklmwtyu', 'Invalid argument given, $api_key is not allowed'];
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
     * @dataProvider invalidDataCharacterApiKeyDataProvider
     *
     * @param mixed $apiKey
     * @param mixed $errorMsg
     */
    public function testWithForbiddenCharaterInApiKeyDataProvider($apiKey, $errorMsg)
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
