<?php

namespace PayPlug\Tests\utilities\helpers\UserHelper;

use PayPlug\src\utilities\helpers\UserHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group helper
 * @group user_helper
 *
 * @dontrunTestsInSeparateProcesses
 */
class isLoggedTest extends TestCase
{
    protected $userHelper;

    protected function setUp()
    {
        $this->userHelper = new UserHelper();
    }

    /**
     * @description invalid IsLogged params provider
     *
     * @return \Generator
     */
    public function loggedDataProvider()
    {
        yield [null, false, '$isEmail must be a bool type'];
        yield ['test', false, '$isEmail must be a bool type'];
        yield [['key' => 'value'], false, '$isEmail must be a bool type'];
        yield [42, false, '$isEmail must be a bool type'];

        yield [true, ['key' => 'value'], '$isApiKey must be a bool type'];
        yield [true, 'lorem ipsum', '$isApiKey must be a bool type'];
        yield [true, null, '$isApiKey must be a bool type'];
        yield [true, 42, '$isApiKey must be a bool type'];
    }

    /**
     * @description  test isLogged with invalid isEmail or isApiKey provider
     *
     * @dataProvider loggedDataProvider
     *
     * @param $isEmail
     * @param $isApiKey
     * @param $message
     */
    public function testIsLoggedWithInvalidDataProvider($isEmail, $isApiKey, $message)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => $message,
            ],
            $this->userHelper->isLogged($isEmail, $isApiKey)
        );
    }

    /**
     * test return with invalid Email.
     */
    public function testIsntLoggedWithInvalidEmail()
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'user is not logged because $email is not valid',
            ],
            $this->userHelper->isLogged(false, true)
        );
    }

    /**
     * description test return with invalid Apikey.
     */
    public function testIsntLoggedWithInvalidApiKey()
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'user is not logged because $isApiKey is not valid',
            ],
            $this->userHelper->isLogged(true, false)
        );
    }
}
