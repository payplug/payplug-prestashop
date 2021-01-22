<?php
//
///**
// * 2013 - 2021 PayPlug SAS
// *
// * NOTICE OF LICENSE
// *
// * This source file is subject to the Open Software License (OSL 3.0).
// * It is available through the world-wide-web at this URL:
// * https://opensource.org/licenses/osl-3.0.php
// * If you are unable to obtain it through the world-wide-web, please send an email
// * to contact@payplug.com so we can send you a copy immediately.
// *
// * DISCLAIMER
// *
// * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
// * versions in the future.
// *
// * @author    PayPlug SAS
// * @copyright 2013 - 2021 PayPlug SAS
// * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
// *  International Registered Trademark & Property of PayPlug SAS
// */
//
//use PayPlug\tests\mock\OneySimulationsMock;
//use PHPUnit\Framework\TestCase;
//
///**
// * @group repository
// * @group oney
// * @group oney_repository
// */
//final class GetOneySimulationsTest extends TestCase
//{
//    protected $cacheId;
//    protected $amount;
//    protected $isoCode;
//    protected $operations;
//    protected $data;
//
//    public function setUp()
//    {
//        $this->amount = 20678;
//        $this->isoCode = 'FR';
//        $this->operations = ['x3_with_fees', 'x4_with_fees'];
//        $this->cacheId = 'Payplug::OneySimulations_' .
//            (int)$this->amount . '_' .
//            (string)$this->isoCode . '_' .
//            (string)implode('_', $this->operations) . '_' .
//            'live';
//        $this->data = [
//            'amount' => $this->amount,
//            'country' => $this->isoCode,
//            'operations' => $this->operations,
//        ];
//    }
//
//    public function testAmount()
//    {
//        $this->assertSame(
//            20678,
//            $this->amount
//        );
//    }
//
//    public function testAmountIsInt()
//    {
//        $this->assertTrue(
//            is_int($this->amount)
//        );
//    }
//
//    public function testIsoCode()
//    {
//        $this->assertSame(
//            'FR',
//            $this->isoCode
//        );
//    }
//
//    public function testIsoCodeIsAString()
//    {
//        $this->assertTrue(
//            is_string($this->isoCode)
//        );
//    }
//
//    public function testOperations()
//    {
//        $this->assertSame(
//            ['x3_with_fees', 'x4_with_fees'],
//            $this->operations
//        );
//    }
//
//    public function testOperationsIsAnArray()
//    {
//        $this->assertTrue(
//            is_array($this->operations)
//        );
//    }
//
//    public function testCacheId()
//    {
//        $this->assertSame(
//            'Payplug::OneySimulations_20678_FR_x3_with_fees_x4_with_fees_live',
//            $this->cacheId
//        );
//    }
//
//    public function testCacheIdIsAString()
//    {
//        $this->assertTrue(
//            is_string($this->cacheId)
//        );
//    }
//
//    public function testCacheIdHasValidatedFormat()
//    {
//        $this->assertRegExp(
//            '/Payplug::OneySimulations_\d{5,6}_[A-Z]{2}_(x\d{1}_with_fees|x\d{1}_with_fees_x\d{1}_with_fees)_live/',
//            $this->cacheId
//        );
//    }
//
//    public function testDataIsAnArray()
//    {
//        $this->assertTrue(
//            is_array($this->data)
//        );
//    }
//
//    public function testSimulationsIsAnArray()
//    {
//        $simulations = OneySimulationsMock::getOneySimulations();
//        $this->assertTrue(
//            is_array($simulations)
//        );
//    }
//
//    public function testSimulationsCountEqualsToOperationsCount()
//    {
//        $simulations = OneySimulationsMock::getOneySimulations();
//        $this->assertEquals(
//            count($simulations),
//            count($this->operations)
//        );
//    }
//
//    public function testSimulationsIsNotAvailable()
//    {
//        $simulations = OneySimulationsMock::getOneySimulationsNotAvailable();
//        $this->assertSame(
//            'Access to this feature is not available.',
//            $simulations['details']
//        );
//    }
//
//    public function testSimulationsIsError()
//    {
//        $simulations = OneySimulationsMock::getOneySimulationsIsError();
//        $this->assertSame(
//            'error',
//            $simulations['object']
//        );
//    }
//}
