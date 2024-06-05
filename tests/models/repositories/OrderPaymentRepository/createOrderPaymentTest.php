<?php

namespace PayPlug\tests\models\repositories\OrderPaymentRepository;

/**
 * @group unit
 * @group repository
 * @group order_payment_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
class createOrderPaymentTest extends BaseOrderPaymentRepository
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $parameters
     */
    public function testWhenGivenParametersIsInvalidArrayFormat($parameters)
    {
        $this->assertFalse($this->repository->createOrderPayment($parameters));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $parameters = [
            'lorem' => 'ipsum',
        ];
        $this
            ->repository
            ->shouldReceive([
                'update' => $this->repository,
                'table' => $this->repository,
                'set' => $this->repository,
                'where' => $this->repository,
                'build' => false,
            ]);

        $this->assertFalse($this->repository->createOrderPayment($parameters));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $parameters = [
            'lorem' => 'ipsum',
        ];
        $this
            ->repository
            ->shouldReceive([
                'update' => $this->repository,
                'table' => $this->repository,
                'set' => $this->repository,
                'where' => $this->repository,
                'build' => true,
            ]);

        $this->assertTrue($this->repository->createOrderPayment($parameters));
    }
}
