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

namespace PayPlug\tests\repositories\PaymentRepository;

/**
 * @group unit
 * @group repository
 * @group payment
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 *
 * @internal
 * @coversNothing
 */
final class ReturnPaymentErrorTest extends BasePaymentRepository
{
    private $response;

    public function setUp()
    {
        parent::setUp();

        $this->response = [
            'result' => false,
            'name' => 'value',
            'response' => 'error message',
        ];
    }

    public function InvalidElementDataProvider()
    {
        yield [null];
        yield ['wrong_parameter'];
        yield [42];
    }

    /**
     * @dataProvider InvalidElementDataProvider
     *
     * @param mixed $element
     */
    public function testMethodWithEmptyElement($element)
    {
        $this->assertSame(
            [
                'result' => false,
                'response' => 'Test with empty $element params',
            ],
            $this->repo->returnPaymentError($element, 'Test with empty $element params')
        );
    }

    public function InvalidMessageDataProvider()
    {
        yield [null];
        yield [['wrong error message']];
        yield [42];
    }

    /**
     * @dataProvider InvalidMessageDataProvider
     *
     * @param mixed $message
     */
    public function testMethodWithWrongMessage($message)
    {
        $element = [
            'name' => 'unit_test',
            'value' => 'empty message',
        ];

        $this->assertSame(
            [
                'result' => false,
                'unit_test' => '"empty message"',
                'response' => '[PaymentRepository] Error during payment creation process.',
            ],
            $this->repo->returnPaymentError($element, $message)
        );
    }

    public function testMethodWithValidData()
    {
        $element = [
            'name' => 'unit_test',
            'value' => 'empty message',
        ];

        $message = 'Test valide return error method';

        $this->assertSame(
            [
                'result' => false,
                'unit_test' => '"empty message"',
                'response' => 'Test valide return error method',
            ],
            $this->repo->returnPaymentError($element, $message)
        );

        $this->assertSame(
            2,
            count($this->arrayLogger)
        );
    }
}
