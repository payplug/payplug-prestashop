<?php

/**
 * 2013 - 2021 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    PayPlug SAS
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\tests\repositories\OrderStateRepository;

use PayPlug\tests\mock\OrderStateMock;

/**
 * @group unit
 * @group repository
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
        $success_return = 'updateType: success';

        $this->repo
            ->shouldReceive([
                'getType' => $this->type,
                'updateType' => $success_return
            ]);

        $this->assertSame(
            $success_return,
            $this->repo->saveType($this->idOrderState, $this->type)
        );
    }

    public function testWithoutExistingOrderStateType()
    {
        $success_return = 'setType: success';

        $this->repo
            ->shouldReceive([
                'getType' => false,
                'setType' => $success_return
            ]);

        $this->assertSame(
            $success_return,
            $this->repo->saveType($this->idOrderState, $this->type)
        );
    }
}
