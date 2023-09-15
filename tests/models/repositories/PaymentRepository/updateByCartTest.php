<?php

namespace PayPlug\tests\models\repositories\PaymentRepository;

/**
 * @group unit
 * @group repository
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
class updateByCartTest extends BasePaymentRepository
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_cart
     */
    public function testWhenGivenIdCartIsInvalidIntegerFormat($id_cart)
    {
        $parameters = [];
        $this->assertFalse($this->repository->updateByCart($id_cart, $parameters));
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $parameters
     */
    public function testWhenGivenParametersIsInvalidArrayFormat($parameters)
    {
        $id_cart = 42;
        $this->assertFalse($this->repository->updateByCart($id_cart, $parameters));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $id_cart = 42;
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

        $this->assertFalse($this->repository->updateByCart($id_cart, $parameters));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $id_cart = 42;
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

        $this->assertTrue($this->repository->updateByCart($id_cart, $parameters));
    }
}
