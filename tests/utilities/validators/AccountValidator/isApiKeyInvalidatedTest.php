<?php

namespace PayPlug\tests\utilities\validators\AccountValidator;

use PayPlug\src\utilities\validators\accountValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group account_validator
 *
 * @dontrunTestsInSeparateProcesses
 */
class isApiKeyInvalidatedTest extends TestCase
{
    protected $accountValidator;

    protected function setUp()
    {
        $this->accountValidator = new AccountValidator();
    }

    public function invalidAccountApiKeyDataProvider()
    {
        yield [42, 'Invalid argument given, $api_key_account must be a non empty string'];
        yield [['key' => 'value'], 'Invalid argument given, $api_key_account must be a non empty string'];
        yield [false, 'Invalid argument given, $api_key_account must be a non empty string'];
        yield ['', 'Invalid argument given, $api_key_account must be a non empty string'];
    }

    public function invalidDatabaseApiKeyDataProvider()
    {
        yield [42, 'Invalid argument given, $api_key_database must be a non empty string'];
        yield [['key' => 'value'], 'Invalid argument given, $api_key_database must be a non empty string'];
        yield [false, 'Invalid argument given, $api_key_database must be a non empty string'];
        yield ['', 'Invalid argument given, $api_key_database must be a non empty string'];
    }

    public function invalidApiKeyDataProvider()
    {
        yield ['sk_live_azertyuiopqsdfghjklmwx', 'sk_live_azertyuiopqsdfghjklmwc', 'The $api_key_account is different from the $api_key_database. Your API key is invalidated'];
    }

    public function validApiKeyDataProvider()
    {
        yield ['sk_live_azertyuiopqsdfghjklmwx', 'sk_live_azertyuiopqsdfghjklmwx', '']; // Success
    }

    /**
     * @dataProvider invalidAccountApiKeyDataProvider
     *
     * @param mixed $apiKeyAccount
     * @param mixed $errorMsg
     */
    public function testWithInvalidAccountApiKeyDataProvider($apiKeyAccount, $errorMsg)
    {
        $apiKeyDatabase = 'sk_live_azertyuiopqsdfghjklmwx';
        $this->assertSame(
            [
                'result' => false,
                'message' => $errorMsg,
            ],
            $this->accountValidator->isApiKeyInvalidated($apiKeyAccount, $apiKeyDatabase)
        );
    }

    /**
     * @dataProvider invalidDatabaseApiKeyDataProvider
     *
     * @param mixed $apiKeyDatabase
     * @param mixed $errorMsg
     */
    public function testWithInvalidDatabaseApiKeyDataProvider($apiKeyDatabase, $errorMsg)
    {
        $apiKeyAccount = 'sk_live_azertyuiopqsdfghjklmwx';
        $this->assertSame(
            [
                'result' => false,
                'message' => $errorMsg,
            ],
            $this->accountValidator->isApiKeyInvalidated($apiKeyAccount, $apiKeyDatabase)
        );
    }

    /**
     * @dataProvider invalidApiKeyDataProvider
     *
     * @param mixed $apiKeyAccount
     * @param mixed $apiKeyDatabase
     * @param mixed $errorMsg
     */
    public function testWithInvalidApiKeyDataProvider($apiKeyAccount, $apiKeyDatabase, $errorMsg)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => $errorMsg,
            ],
            $this->accountValidator->isApiKeyInvalidated($apiKeyAccount, $apiKeyDatabase)
        );
    }

    /**
     * @dataProvider validApiKeyDataProvider
     *
     * @param mixed $apiKeyAccount
     * @param mixed $apiKeyDatabase
     * @param mixed $msg
     */
    public function testWithValidApiKeyDataProvider($apiKeyAccount, $apiKeyDatabase, $msg)
    {
        $this->assertSame(
            [
                'result' => true,
                'message' => $msg,
            ],
            $this->accountValidator->isApiKeyInvalidated($apiKeyAccount, $apiKeyDatabase)
        );
    }
}
