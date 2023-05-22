<?php

namespace PayPlug\tests\repositories\CardRepository;

use PayPlug\tests\mock\PayPlugCardMock;

/**
 * @group unit
 * @group repository
 * @group card_repository
 *
 * @runTestsInSeparateProcesses
 */
final class DeleteCardTest extends BaseCardRepository
{
    private $payplug_card;

    public function setUp()
    {
        parent::setUp();

        $this->payplug_card = PayPlugCardMock::get();
    }

    public function invalidDataProvider()
    {
        // invalid int $id_customer
        yield [null, 42];
        yield [false, 42];
        yield ['I am a string!', 42];
        yield [['key' => 'value'], 42];

        // invalid int $id_payplug_card
        yield [42, null];
        yield [42, false];
        yield [42, 'I am a string!'];
        yield [42, ['key' => 'value']];
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param $id_customer
     * @param $id_payplug_card
     */
    public function testWithInvalidParams($id_customer, $id_payplug_card)
    {
        $this->assertFalse($this->repo->deleteCard($id_customer, $id_payplug_card));
    }

    public function testWhenNoCardFound()
    {
        $this->repositories['card']->shouldReceive([
            'get' => [],
        ]);

        $this->assertFalse($this->repo->deleteCard(
            $this->payplug_card['id_customer'],
            $this->payplug_card['id_payplug_card']
        ));
    }

    public function testWhenCardNotExpiredAndErrorReturnByAPI()
    {
        $this->repositories['card']->shouldReceive([
            'get' => $this->payplug_card,
        ]);
        $this->repo
            ->shouldReceive([
                'deleteCardFromAPI' => false,
            ])
        ;

        $this->assertFalse($this->repo->deleteCard(
            $this->payplug_card['id_customer'],
            $this->payplug_card['id_payplug_card']
        ));
    }

    public function testWhenCardCantBeDeletedFromDataBase()
    {
        $this->repositories['card']->shouldReceive([
            'get' => $this->payplug_card,
            'remove' => false,
        ]);

        $this->repo
            ->shouldReceive([
                'deleteCardFromAPI' => true,
            ])
        ;

        $this->assertFalse($this->repo->deleteCard(
            $this->payplug_card['id_customer'],
            $this->payplug_card['id_payplug_card']
        ));
    }

    public function testWhenCardIsDeletedFromDataBase()
    {
        $this->repositories['card']->shouldReceive([
            'get' => $this->payplug_card,
            'remove' => true,
        ]);

        $this->repo
            ->shouldReceive([
                'deleteCardFromAPI' => true,
            ])
        ;

        $this->assertTrue(
            $this->repo->deleteCard(
                $this->payplug_card['id_customer'],
                $this->payplug_card['id_payplug_card']
            )
        );
    }

    public function testWhenCardExpired()
    {
        $card = $this->payplug_card;
        $card['exp_year'] = 2020;

        $this->repositories['card']->shouldReceive([
            'get' => $card,
            'remove' => true,
        ]);

        $this->assertTrue($this->repo->deleteCard(
            $this->payplug_card['id_customer'],
            $this->payplug_card['id_payplug_card']
        ));
    }
}
