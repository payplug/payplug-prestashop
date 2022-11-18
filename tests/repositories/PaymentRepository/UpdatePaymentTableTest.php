<?php

namespace PayPlug\tests\repositories\PaymentRepository;

use PayPlug\tests\mock\CartMock;

/**
 * @group unit
 * @group repository
 * @group payment
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
final class UpdatePaymentTableTest extends BasePaymentRepository
{
    private $paymentDetails;

    public function setUp()
    {
        parent::setUp();

        $this->paymentDetails = [
            'cart' => CartMock::get(),
            'paymentId' => 1,
            'paymentMethod' => 'standard',
            'paymentUrl' => 'htt://www.monsite.com',
            'paymentReturnUrl' => 'htt://www.monsite.com',
            'authorizedAt' => '2021-01-01 00:00:00',
            'isPaid' => true,
            'cartId' => 1,
        ];
    }

    /**
     * Parameters to test method with empty $paiementDetails
     *
     * @return \Generator
     */
    public function invalidDataProvider()
    {
        yield [null, 'paymentDetails: null'];
        yield [[(string) 'I am a string!'], 'paymentDetails: ["I am a string!"]'];
        yield [['cart' => null], 'paymentDetails: {"cart":null}'];
    }

    /**
     * Test methods with nulled $paiementDetails
     *
     * @dataProvider invalidDataProvider
     *
     * @param array  $parameter
     * @param string $logMessage
     */
    public function testMethodWithInvalidData($parameter, $logMessage)
    {
        $this->repo
            ->shouldReceive([
                'returnPaymentError' => $logMessage,
            ])
        ;

        $this->assertSame(
            $this->repo->updatePaymentTable($parameter),
            $logMessage
        );
    }

    public function testWithUpdateThrowingException()
    {
        $expected_error = [
            ['name' => 'paymentDetails', 'value' => $this->paymentDetails],
            '[updatePaymentTable] Unable to fetch the query on DB. Error: Build method throw exception',
        ];

        $this->query
            ->shouldReceive([
                'update' => $this->query,
                'table' => $this->query,
                'set' => $this->query,
                'where' => $this->query,
            ])
        ;

        $this->query
            ->shouldReceive('build')
            ->andThrow('Exception', 'Build method throw exception', 500)
        ;

        $this->repo
            ->shouldReceive([
                'returnPaymentError' => $expected_error,
                'getHashedCart' => 'b0a30e26e83b2a',
            ])
        ;

        $this->assertSame(
            $expected_error,
            $this->repo->updatePaymentTable($this->paymentDetails)
        );
    }

    public function testWithUpdateReturningError()
    {
        $expected_error = [
            ['name' => 'paymentDetails', 'value' => $this->paymentDetails],
            '[updatePaymentTable] Unable to fetch the query on DB but no throw',
        ];

        $this->query
            ->shouldReceive([
                'update' => $this->query,
                'table' => $this->query,
                'set' => $this->query,
                'where' => $this->query,
                'build' => false,
            ])
        ;

        $this->repo
            ->shouldReceive([
                'returnPaymentError' => $expected_error,
                'getHashedCart' => 'b0a30e26e83b2a',
            ])
        ;

        $this->assertSame(
            $expected_error,
            $this->repo->updatePaymentTable($this->paymentDetails)
        );
    }

    public function testWithValidMethod()
    {
        $this->query
            ->shouldReceive([
                'update' => $this->query,
                'table' => $this->query,
                'set' => $this->query,
                'where' => $this->query,
                'build' => true,
            ])
        ;

        $this->repo
            ->shouldReceive([
                'getHashedCart' => 'b0a30e26e83b2a',
            ])
        ;

        $this->assertSame(
            [
                'result' => true,
                'paymentDetails' => $this->paymentDetails,
                'response' => 'Update DB with new payment creation successfully',
            ],
            $this->repo->updatePaymentTable($this->paymentDetails)
        );
    }

    public function testUpdatePaymentTableWithInvalidHashedCart()
    {
        $this->paymentDetails['cart'] = 'Invalid Cart';

        $this->query
            ->shouldReceive([
                'update' => $this->query,
                'table' => $this->query,
                'set' => $this->query,
                'where' => $this->query,
                'build' => true,
            ])
        ;

        $this->assertSame(
            $this->repo->updatePaymentTable($this->paymentDetails)['response'],
            '[updatePaymentTable] Problem with the getHashedCart method.'
        );
    }
}
