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

namespace PayPlug\tests\repositories\CardRepository;

/**
 * @group unit
 * @group repository
 * @group card_repository
 *
 * @runTestsInSeparateProcesses
 */
final class DeleteCardsTest extends BaseCardRepository
{
    private $id_customer;
    private $cards;

    public function setUp()
    {
        parent::setUp();

        $this->id_customer = 42;
        $this->cards = [[
            'last4' => 4242,
            'country' => 'FR',
            'exp_year' => 2023,
            'exp_month' => 03,
            'brand' => 'Visa',
            'id_payplug_card' => 2
        ]];
    }

    public function invalidDataProvider()
    {
        yield [null];
        yield ['I am a string!'];
        yield [['wrong_parameters' => 'value']];
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testWithInvalidParams($id_customer)
    {
        $this->assertFalse($this->repo->deleteCards($id_customer));
    }

    public function testWithoutCardToDeleted()
    {
        $this->repo
            ->shouldReceive('getByCustomer')
            ->andReturn([]);

        $this->assertTrue($this->repo->deleteCards($this->id_customer));
    }

    public function testCardCantBeDeleted()
    {
        $this->repo
            ->shouldReceive([
                'getByCustomer' => $this->cards,
                'deleteCard' => false
            ]);

        $this->assertFalse($this->repo->deleteCards($this->id_customer));
    }

    public function tesCardCanBeDeleted()
    {
        $this->repo
            ->shouldReceive([
                'getByCustomer' => $this->cards,
                'deleteCard' => true
            ]);

        $this->assertTrue($this->repo->deleteCards($this->id_customer));
    }
}
