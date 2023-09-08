<?php

namespace PayPlug\tests\repositories\CardRepository;

/**
 * @group unit
 * @group old_repository
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
            'id_payplug_card' => 2,
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
     *
     * @param mixed $id_customer
     */
    public function testWithInvalidParams($id_customer)
    {
        $this->assertFalse($this->repo->deleteCards($id_customer));
    }

    public function testWithoutCardToDeleted()
    {
        $this->repo
            ->shouldReceive('getByCustomer')
            ->andReturn([])
        ;

        $this->assertTrue($this->repo->deleteCards($this->id_customer));
    }

    public function testCardCantBeDeleted()
    {
        $this->repo
            ->shouldReceive([
                'getByCustomer' => $this->cards,
                'deleteCard' => false,
            ])
        ;

        $this->assertFalse($this->repo->deleteCards($this->id_customer));
    }

    public function tesCardCanBeDeleted()
    {
        $this->repo
            ->shouldReceive([
                'getByCustomer' => $this->cards,
                'deleteCard' => true,
            ])
        ;

        $this->assertTrue($this->repo->deleteCards($this->id_customer));
    }
}
