<?php

namespace PayPlug\tests\models\classes\paymentMethod\InstallmentPaymentMethod;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group installment_payment_method_class
 *
 * @runTestsInSeparateProcesses
 */
class retrieveSchedulesTest extends BaseInstallmentPaymentMethod
{
    public function setUp()
    {
        parent::setUp();
        $this->configuration
            ->shouldReceive('getValue')
            ->with('sandbox_mode')
            ->andReturn(true);
        $this->configuration
            ->shouldReceive('getValue')
            ->with('test_api_key')
            ->andReturn('test_api_key');
        $this->configuration
            ->shouldReceive('getValue')
            ->with('live_api_key')
            ->andReturn('live_api_key');
        $this->api_service->shouldReceive([
            'initialize' => true,
        ]);
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $retrieve
     */
    public function testWhenGivenRetrieveIsntValidArrayFormat($retrieve)
    {
        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'Invalid argument, $retrieve must be a non empty array.',
            ],
            $this->class->retrieveSchedules($retrieve)
        );
    }

    public function testWhenExpectedRetrieveResultIsFalse()
    {
        $retrieve = [
            'result' => false,
            'resource' => PaymentMock::getStandard(),
        ];
        $this->assertSame(
            $retrieve,
            $this->class->retrieveSchedules($retrieve)
        );
    }

    public function testWhenGivenRetrieveResourceHasNoSchedules()
    {
        $retrieve = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $this->assertSame(
            $retrieve,
            $this->class->retrieveSchedules($retrieve)
        );
    }

    public function testWhenPaymentResourceRelatedToScheduleCantBeRetrieve()
    {
        $retrieve = [
            'result' => true,
            'resource' => PaymentMock::getInstallment(),
        ];
        $this->api_service->shouldReceive([
            'retrievePayment' => [
                'result' => false,
            ],
        ]);
        $expected = $retrieve;
        $expected['schedule'] = [
            [
                'amount' => 10440,
                'date' => '2021-03-05',
                'resource' => null,
            ],
            [
                'amount' => 10440,
                'date' => '2021-04-04',
                'resource' => null,
            ],
            [
                'amount' => 10440,
                'date' => '2021-05-04',
                'resource' => null,
            ],
        ];
        $this->assertSame(
            $expected,
            $this->class->retrieveSchedules($retrieve)
        );
    }

    public function testWhenInstallmentSchedulesAreRetrieved()
    {
        $retrieve = [
            'result' => true,
            'resource' => PaymentMock::getInstallment(),
        ];
        $schedule_resource = PaymentMock::getStandard();
        $this->api_service->shouldReceive([
            'retrievePayment' => [
                'result' => true,
                'resource' => $schedule_resource,
            ],
        ]);
        $expected = $retrieve;
        $expected['schedule'] = [
            [
                'amount' => 10440,
                'date' => '2021-03-05',
                'resource' => $schedule_resource,
            ],
            [
                'amount' => 10440,
                'date' => '2021-04-04',
                'resource' => null,
            ],
            [
                'amount' => 10440,
                'date' => '2021-05-04',
                'resource' => null,
            ],
        ];
        $this->assertSame(
            $expected,
            $this->class->retrieveSchedules($retrieve)
        );
    }
}
