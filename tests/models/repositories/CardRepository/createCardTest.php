<?php

namespace PayPlug\tests\models\repositories\CardRepository;

/**
 * @group unit
 * @group repository
 * @group card_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
class createCardTest extends BaseCardRepository
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $parameters
     */
    public function testWhenGivenParametersIsInvalidArrayFormat($parameters)
    {
        $this->assertFalse($this->repository->createCard($parameters));
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

        $this->assertFalse($this->repository->createCard($parameters));
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

        $this->assertTrue($this->repository->createCard($parameters));
    }
}
