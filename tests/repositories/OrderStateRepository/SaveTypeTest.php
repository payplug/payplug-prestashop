<?php

namespace PayPlug\tests\repositories\OrderStateRepository;

/**
 * @group unit
 * @group old_repository
 * @group order_state
 * @group order_state_repository
 *
 * @runTestsInSeparateProcesses
 */
final class SaveTypeTest extends BaseOrderStateRepository
{
    private $idOrderState;
    private $type;

    public function setUp()
    {
        parent::setUp();

        $this->idOrderState = 42;
        $this->type = 'nothing';
    }

    public function invalidDataProvider()
    {
        // test invalid id_order_state
        yield [['wrong_value'], 'nothing'];
        yield ['wrong_value', 'nothing'];
        yield [0, 'nothing'];
        yield ['', 'nothing'];
        yield [false, 'nothing'];
        yield [null, 'nothing'];

        // test invalid type
        yield [42, ['wrong_value']];
        yield [42, 42];
        yield [42, ''];
        yield [42, false];
        yield [42, null];
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param mixed $id_order_state
     * @param mixed $type
     */
    public function testWithInvalidDataProvider($id_order_state, $type)
    {
        $this->assertSame(
            false,
            $this->repo->saveType($id_order_state, $type)
        );
    }

    public function testWithExistingOrderStateType()
    {
        $this->payplug_order_state_repository
            ->shouldReceive([
                'updateEntity' => true,
                'getBy' => [
                    'id_payplug_order_state' => 42,
                    'type' => $this->type,
                ],
            ]);

        $this->assertSame(
            true,
            $this->repo->saveType($this->idOrderState, $this->type)
        );
    }

    public function testWithoutExistingOrderStateType()
    {
        $this->payplug_order_state_repository
            ->shouldReceive([
                'createEntity' => true,
                'getBy' => [],
            ]);

        $this->assertSame(
            true,
            $this->repo->saveType($this->idOrderState, $this->type)
        );
    }
}
