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
class hasLiveKeyTest extends TestCase
{
    protected $accountValidator;

    public function setUp()
    {
        $this->accountValidator = new AccountValidator();
    }

    public function hasInvalideTypeApiKeyDataProvider()
    {
        yield [42, 'The account does not have LIVE API key'];

        yield [['key' => 'value'], 'The account does not have LIVE API key'];

        yield [false, 'The account does not have LIVE API key'];

        yield ['', 'The account does not have LIVE API key'];
    }

    public function hasValideApiKeyDataProvider()
    {
        yield ['sk_live_azertyuiopqsdfghjklmwx', '']; // Success
    }

    /**
     * @dataProvider hasInvalideTypeApiKeyDataProvider
     *
     * @param mixed $apiKey
     * @param mixed $errorMsg
     */
    public function testWithInvalidTypeLiveApiKeyDataProvider($apiKey, $errorMsg)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => $errorMsg,
            ],
            $this->accountValidator->hasLiveKey($apiKey)
        );
    }

    /**
     * @dataProvider hasValideApiKeyDataProvider
     *
     * @param mixed $apiKey
     * @param mixed $msg
     */
    public function testWithValidLiveApiKeyDataProvider($apiKey, $msg)
    {
        $this->assertSame(
            [
                'result' => true,
                'message' => $msg,
            ],
            $this->accountValidator->hasLiveKey($apiKey)
        );
    }
}
