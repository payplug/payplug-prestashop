<?php

/**
 * 2013 - 2021 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    PayPlug SAS
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

use PayPlug\src\repositories\OneyRepository;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;
use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class isValidOneyEmailTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    // Default setup
    protected $config;
    protected $myLogPhp;

    // Method setup
    protected $validate;
    protected $tools;
    protected $translate;

    // Method Params
    protected $repo;
    protected $email;

    public function setUp()
    {
        // Default setup for Oney Repository using
        $this->config = MockHelper::createMockFactory('Payplug\src\specific\ConfigurationSpecific');
        $this->myLogPhp = MockHelper::createMockFactory('Payplug\classes\MyLogPHP');

        // Method setup
        $this->validate = MockHelper::createValidateMock('Payplug\src\specific\ValidateSpecific');
        $this->tools = MockHelper::createToolsMock('Payplug\src\specific\ToolsSpecific');
        $this->translate = MockHelper::createTranslateMock('Payplug\src\specific\TranslationSpecific');

        // Method Params
        $this->email = 'mock@payplug.com';

        $this->repo = new OneyRepository('');
    }

    public function testWithValidEmail()
    {
        $response = $this->repo->isValidOneyEmail($this->email);
        $this->assertSame(
            [
                'result' => true,
                'message' => false,
            ],
            $response
        );
    }

    public function testWithEmptyEmail()
    {
        $response = $this->repo->isValidOneyEmail('');
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Your email address is not a valid email',
            ],
            $response
        );
    }

    public function testWithInValidEmail()
    {
        $this->validate->andReturn(false); // force Validate::isEmail = false
        $response = $this->repo->isValidOneyEmail('mock-payplug.com');
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Your email address is not a valid email',
            ],
            $response
        );
    }

    public function testWithInValidEmailFormat()
    {
        $response = $this->repo->isValidOneyEmail([$this->email]);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Your email address is not a valid email',
            ],
            $response
        );
    }

    public function testWithForbiddenChar()
    {
        $error_email = 'test+' . $this->email;
        $response = $this->repo->isValidOneyEmail($error_email);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'The + character is not valid. 
                        Please change your email address (100 characters max).'
            ],
            $response
        );
    }

    public function testWithTooLongEmail()
    {
        $max_lenght = 100;
        $error_email = '';
        for ($i=0;$i<$max_lenght;$i++) {
            $error_email .= 'a';
        }
        $error_email .= $this->email;
        $response = $this->repo->isValidOneyEmail($error_email);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Your email address is too long, 
                        please change it to a shorter one (max 100 characters).'
            ],
            $response
        );
    }

    public function testWithTooLongAndWrongEmail()
    {
        $max_lenght = 100;
        $error_email = '';
        for ($i=0;$i<$max_lenght;$i++) {
            $error_email .= 'a';
        }
        $error_email .= '+' . $this->email;
        $response = $this->repo->isValidOneyEmail($error_email);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Your email address is too long and the + character is not valid, 
                        please change it to another address (max 100 characters).'
            ],
            $response
        );
    }
}
