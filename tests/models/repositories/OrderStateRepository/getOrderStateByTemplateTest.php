<?php

namespace PayPlug\tests\models\repositories\OrderStateRepository;

/**
 * @group unit
 * @group repository
 * @group order_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
class getOrderStateByTemplateTest extends BaseOrderStateRepository
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $template
     */
    public function testWhenGivenTemplateIsInvalidStringFormat($template)
    {
        $this->assertSame(
            [],
            $this->repository->getOrderStateByTemplate($template)
        );
    }

    public function testWhenFailedRetrievingInDatabase()
    {
        $template = 'paid';

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => false,
        ]);

        $this->assertSame(
            [],
            $this->repository->getOrderStateByTemplate($template)
        );
    }

    public function testWhenSucceedRetrievingInDatabase()
    {
        $template = 'paid';

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => [],
        ]);

        $this->assertSame(
            [],
            $this->repository->getOrderStateByTemplate($template)
        );
    }
}
