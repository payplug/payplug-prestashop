<?php

namespace PayPlug\tests\repositories\CardRepository;

/**
 * @group unit
 * @group old_repository
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
                'id_payplug_card' => 2,
            ],
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
     *
     * @param $id_customer
     * @param $id_payplug_card
     * @param mixed $active_only
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
        $this->card_repository->shouldReceive([
            'getAllByCustomer' => [],
        ]);

        $this->assertSame(
            [],
            $this->repo->getByCustomer($this->id_customer, true)
        );
    }

    public function testWhenCardsIsExpiredAndAllowed()
    {
        $this->card_repository->shouldReceive([
            'getAllByCustomer' => $this->cards,
        ]);

        $this->validators['card']->shouldReceive([
            'isValidExpiration' => [
                'result' => false,
            ],
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
                ],
            ],
            $this->repo->getByCustomer($this->id_customer, false)
        );
    }

    public function testWhenCardsIsExpiredAndDisallowed()
    {
        $this->card_repository->shouldReceive([
            'getAllByCustomer' => $this->cards,
        ]);

        $this->validators['card']->shouldReceive([
            'isValidExpiration' => [
                'result' => false,
            ],
        ]);

        $this->assertSame(
            [],
            $this->repo->getByCustomer($this->id_customer, true)
        );
    }

    public function testWhenCardsIsActive()
    {
        $this->card_repository->shouldReceive([
            'getAllByCustomer' => $this->cards,
        ]);

        $this->validators['card']->shouldReceive([
            'isValidExpiration' => [
                'result' => true,
            ],
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
                ],
            ],
            $this->repo->getByCustomer($this->id_customer, false)
        );
    }
}
