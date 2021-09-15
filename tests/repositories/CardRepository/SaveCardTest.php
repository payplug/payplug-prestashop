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

namespace PayPlug\tests\repositories\CardRepository;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group repository
 * @group card_repository
 *
 * @runTestsInSeparateProcesses
 */
final class SaveCardTest extends BaseCardRepository
{
    private $payment;
    public function setUp()
    {
        parent::setUp();

        $this->payment = PaymentMock::getOneClick();
        $this->config
            ->shouldReceive('get')
            ->with('PAYPLUG_SANDBOX_MODE')
            ->andReturn(false);
        $this->config
            ->shouldReceive('get')
            ->with('PAYPLUG_COMPANY_ID')
            ->andReturn(4242);
    }

    public function invalidDataProvider()
    {
        yield[false];
        yield[null];
        yield[42];
        yield['wrong parameter'];
        yield[['key'=>'value']];
    }

    /**
     * @dataProvider invalidDataProvider
     * @param $month
     * @param $year
     */
    public function testWithInvalidParams($payment)
    {
        $this->assertFalse($this->repo->saveCard($payment));
    }

    public function testWhenCardAlreadyExists()
    {
        $this->repo
            ->shouldReceive([
                'checkExists' => true
            ]);

        $this->assertFalse($this->repo->saveCard($this->payment));
    }

    public function testWhenDataBaseThrowingException()
    {
        $this->repo
            ->shouldReceive([
                'checkExists' => false
            ]);

        $this->query
            ->shouldReceive([
                'insert' => $this->query,
                'into' => $this->query,
                'fields' => $this->query,
                'values' => $this->query,
            ]);

        $this->query
            ->shouldReceive('build')
            ->andThrow('Exception', 'Build method throw exception', 500);

        $this->assertFalse($this->repo->saveCard($this->payment));
    }

    public function testWhenDataBaseReturnFalse()
    {
        $this->repo
            ->shouldReceive([
                'checkExists' => false
            ]);

        $this->query
            ->shouldReceive([
                'insert' => $this->query,
                'into' => $this->query,
                'fields' => $this->query,
                'values' => $this->query,
                'build' => false,
            ]);

        $this->assertFalse($this->repo->saveCard($this->payment));
    }

    public function testWhenCardIsSaved()
    {
        $this->repo
            ->shouldReceive([
                'checkExists' => false
            ]);

        $this->query
            ->shouldReceive([
                'insert' => $this->query,
                'into' => $this->query,
                'fields' => $this->query,
                'values' => $this->query,
                'build' => true,
            ]);

        $this->assertTrue($this->repo->saveCard($this->payment));
    }
}
