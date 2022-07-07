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
final class GetByCustomerTest extends BaseCardRepository
{
    private $id_customer;
    private $cards;

    public function setUp()
    {
        parent::setUp();

        $this->id_customer = 42;
        $this->cards = [
            [
                'last4' => 4242,
                'country' => 'FR',
                'exp_year' => 2023,
                'exp_month' => 03,
                'brand' => 'Visa',
                'id_payplug_card' => 2
            ]
        ];
    }

    public function invalidDataProvider()
    {
        yield [null, true];
        yield [false, true];
        yield ['I am a string!', true];
        yield [['wrong_parameters' => 'value'], true];
    }

    /**
     * @dataProvider invalidDataProvider
     * @param $id_customer
     * @param $id_payplug_card
     */
    public function testWithInvalidParams($id_customer, $active_only)
    {
        $this->assertSame(
            [],
            $this->repo->getByCustomer($id_customer, $active_only)
        );
    }

    public function testWhenNoCardFoundForGivenCustomerId()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => false
            ]);

        $this->assertSame(
            [],
            $this->repo->getByCustomer($this->id_customer, true)
        );
    }

    public function testWhenCardsIsExpiredAndAllowed()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => $this->cards
            ]);

        $this->repo
            ->shouldReceive([
                'isValidExpiration' => false
            ]);

        $this->assertSame(
            [
                [
                    'last4' => 4242,
                    'country' => 'FR',
                    'exp_year' => 2023,
                    'exp_month' => 3,
                    'brand' => 'Visa',
                    'id_payplug_card' => 2,
                    'expired' => true,
                    'expiry_date' => '03 / 23',
                ]
            ],
            $this->repo->getByCustomer($this->id_customer, false)
        );
    }

    public function testWhenCardsIsExpiredAndDisallowed()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => $this->cards
            ]);

        $this->repo
            ->shouldReceive([
                'isValidExpiration' => false
            ]);

        $this->assertSame(
            [],
            $this->repo->getByCustomer($this->id_customer, true)
        );
    }

    public function testWhenCardsIsActive()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => $this->cards
            ]);

        $this->repo
            ->shouldReceive([
                'isValidExpiration' => true
            ]);

        $this->assertSame(
            [
                [
                    'last4' => 4242,
                    'country' => 'FR',
                    'exp_year' => 2023,
                    'exp_month' => 3,
                    'brand' => 'Visa',
                    'id_payplug_card' => 2,
                    'expired' => false,
                    'expiry_date' => '03 / 23',
                ]
            ],
            $this->repo->getByCustomer($this->id_customer, false)
        );
    }
}
