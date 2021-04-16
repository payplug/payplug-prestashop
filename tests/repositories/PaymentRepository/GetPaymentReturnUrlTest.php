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
 */
final class GetPaymentReturnUrlTest extends BasePaymentRepository
{
    private $paymentDetails;

    public function setUp()
    {
        parent::setUp();

        $this->paymentDetails = [
            'paymentReturnUrl' => 'payment_return_url',
            'paymentUrl' => 'payment_url'
        ];
    }

    /**
     * Parameters to test method with empty $paiementDetails
     *
     * @return \Generator
     */
    public function checkGetPaymentReturnUrlParameters()
    {
        // Test if (!$paymentDetails)
        yield [null, 'paymentDetails: null'];
    }

    public function checkGetPaymentReturnUrlPaymentMethods()
    {
        yield ['oneclick'];
        yield ['oney'];
        yield ['standard'];
        yield ['installment'];
    }


    /**
     * Test methods with nulled $paiementDetails
     *
     * @dataProvider checkGetPaymentReturnUrlParameters
     * @param array $parameter
     * @param string $logMessage
     */
    public function testMethodWithEmptyParams($parameter, $logMessage)
    {
        $response = $this->repo->getpaymentReturnUrl($parameter);

        $this->assertFalse(
            $response['result'],
            'ERROR : the response is true'
        );

        $this->assertSame(
            $response['response'],
            '[getPaymentReturnUrl] $paymentDetails is null'
        );

        $this->assertSame(
            $this->arrayLogger['message'],
            $logMessage
        );
    }

    /**
     * @group returnUrl
     * @dataProvider checkGetPaymentReturnUrlPaymentMethods
     * @param string $paymentMethod
     */
    public function testMethodWithValidData($paymentMethod)
    {
        $this->repo
            ->shouldReceive([
                'checkPaymentTable' => ['key' => 'value']
            ]);

        $this->paymentDetails['paymentMethod'] = $paymentMethod;

        $this->assertTrue($this->repo->getPaymentReturnUrl($this->paymentDetails)['result']);

        $this->assertSame('payment_url', $this->repo->getPaymentReturnUrl($this->paymentDetails)['url']['return_url']);

        $this->assertSame(
            'Return URL successfully generated',
            $this->repo->getPaymentReturnUrl($this->paymentDetails)['response']
        );
    }

    /**
     * @group returnUrl
     */
    public function testMethodWithInvalidData()
    {
        // Step 1 : With return false in checkPaymentTable
        $this->repo
            ->shouldReceive([
                'checkPaymentTable' => false
            ]);

        $this->assertFalse($this->repo->getPaymentReturnUrl($this->paymentDetails)['result']);
        $this->assertSame(
            $this->repo->getPaymentReturnUrl($this->paymentDetails)['response'],
            '[getPaymentReturnUrl] $paymentStored is null or invalid'
        );

        // Step 2 : With good return in checkPaymentTable but invalid payment method
        $this->repo
            ->shouldReceive([
                'checkPaymentTable' => ['key' => 'value']
            ]);

        $this->paymentDetails['paymentMethod'] = mt_rand();

        $this->assertFalse($this->repo->getPaymentReturnUrl($this->paymentDetails)['result']);
        $this->assertSame(
            $this->repo->getPaymentReturnUrl($this->paymentDetails)['response'],
            '[getPaymentReturnUrl] $paymentStored is null or invalid'
        );
    }
}
