<?php

namespace PayPlug\tests\repositories\CardRepository;

use Mockery;
use Payplug\Payment;
use PayPlug\src\models\entities\CardEntity;
use PayPlug\src\repositories\CardRepository;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\repositories\RepositoryBase;

class BaseCardRepository extends RepositoryBase
{
    protected $cardApi;
    protected $cardEntity;
    protected $paymentApi;

    public function setUp()
    {
        parent::setUp();

        $this->cardEntity = new CardEntity();
        $this->logger->shouldReceive([
            'setProcess' => $this->logger,
        ]);

        $this->repo = Mockery::mock(CardRepository::class, [
            $this->constant,
            $this->dependencies,
            $this->logger,
            $this->query,
            $this->tools,
        ])->makePartial();

        $this->arrayLogger = [];
        MockHelper::createAddLogMock($this->logger, $this->arrayLogger);

        $this->cardApi = Mockery::mock('alias:' . Card::class);
        $this->paymentApi = Mockery::mock('alias:' . Payment::class);

        $this->configuration
            ->shouldReceive('getValue')
            ->with('sandbox_mode')
            ->andReturn(false);
        $this->configuration
            ->shouldReceive('getValue')
            ->with('company_id')
            ->andReturn('42');
    }
}
