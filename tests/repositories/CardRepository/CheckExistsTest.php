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

/**
 * @group unit
 * @group repository
 * @group card_repository
 *
 * @runTestsInSeparateProcesses
 */
final class CheckExistsTest extends BaseCardRepository
{
    private $paymentId;
    private $companyId;
    public function setUp()
    {
        parent::setUp();

        $this->paymentId = 'pay_id';
        $this->companyId = 42;
    }

    public function invalidDataProvider()
    {
        // invalid string $paymentId
        yield[false, 42];
        yield[null, 42];
        yield[42, 42];
        yield[['key'=>'value'], 42];

        // invalid int $companyId
        yield['pay_id', false];
        yield['pay_id', null];
        yield['pay_id', 'wrong parameter'];
        yield['pay_id', ['key'=>'value']];
    }

    /**
     * @dataProvider invalidDataProvider
     * @param $month
     * @param $year
     */
    public function testWithInvalidParams($paymentId, $companyId)
    {
        $this->assertFalse($this->repo->checkExists($paymentId, $companyId));
    }

    public function testWhenDataBaseThrowingException()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
            ]);

        $this->query
            ->shouldReceive('build')
            ->andThrow('Exception', 'Build method throw exception', 500);

        $this->assertFalse($this->repo->checkExists($this->paymentId, $this->companyId));
    }

    public function testWhenDataBaseReturnFalse()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => false,
            ]);

        $this->assertFalse($this->repo->checkExists($this->paymentId, $this->companyId));
    }

    public function testWhenACardExists()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => true,
            ]);

        $this->assertTrue($this->repo->checkExists($this->paymentId, $this->companyId));
    }
}
