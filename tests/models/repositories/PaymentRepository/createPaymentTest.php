<?php

namespace PayPlug\tests\models\repositories\PaymentRepository;

/**
 * @group unit
 * @group repository
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
class createPaymentTest extends BasePaymentRepository
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $parameters
     */
    public function testWhenGivenParametersIsInvalidArrayFormat($parameters)
    {
        $this->assertFalse($this->repository->createPayment($parameters));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $parameters = [
            'lorem' => 'ipsum',
        ];
        $this
            ->repository
            ->shouldReceive([
                'insert' => $this->repository,
                'into' => $this->repository,
                'fields' => $this->repository,
                'values' => $this->repository,
                'build' => false,
            ]);

        $this->assertFalse($this->repository->createPayment($parameters));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $parameters = [
            'lorem' => 'ipsum',
        ];
        $this
            ->repository
            ->shouldReceive([
                'insert' => $this->repository,
                'into' => $this->repository,
                'fields' => $this->repository,
                'values' => $this->repository,
                'build' => true,
            ]);

        $this->assertTrue($this->repository->createPayment($parameters));
    }
}
