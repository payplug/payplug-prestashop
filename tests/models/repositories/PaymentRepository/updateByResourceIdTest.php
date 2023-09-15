<?php

namespace PayPlug\tests\models\repositories\PaymentRepository;

/**
 * @group unit
 * @group repository
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
class updateByResourceIdTest extends BasePaymentRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository
            ->shouldReceive('escape')
            ->andReturnUsing(function ($value) {
                return $value;
            });
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testWhenGivenResourceIdIsInvalidIntegerFormat($resource_id)
    {
        $parameters = [];
        $this->assertFalse($this->repository->updateByResourceId($resource_id, $parameters));
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $parameters
     */
    public function testWhenGivenParametersIsInvalidArrayFormat($parameters)
    {
        $resource_id = 'pay_AZERTY123456';
        $this->assertFalse($this->repository->updateByResourceId($resource_id, $parameters));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $resource_id = 'pay_AZERTY123456';
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

        $this->assertFalse($this->repository->updateByResourceId($resource_id, $parameters));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $resource_id = 'pay_AZERTY123456';
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

        $this->assertTrue($this->repository->updateByResourceId($resource_id, $parameters));
    }
}
