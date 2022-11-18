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
        $this->repo
            ->shouldReceive([
                'getCard' => false,
            ])
        ;

        $this->assertFalse($this->repo->deleteCard(
            $this->payplug_card['id_customer'],
            $this->payplug_card['id_payplug_card']
        ));
    }

    public function testWhenCardNotExpiredAndErrorReturnByAPI()
    {
        $this->repo
            ->shouldReceive([
                'getCard' => $this->payplug_card,
                'isValidExpiration' => true,
                'deleteCardFromAPI' => false,
            ])
        ;

        $this->assertFalse($this->repo->deleteCard(
            $this->payplug_card['id_customer'],
            $this->payplug_card['id_payplug_card']
        ));
    }

    public function testWhenDeleteCardFromDataBaseThrowingException()
    {
        $this->repo
            ->shouldReceive([
                'getCard' => $this->payplug_card,
                'isValidExpiration' => true,
                'deleteCardFromAPI' => true,
            ])
        ;

        $this->query
            ->shouldReceive([
                'delete' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
            ])
        ;

        $this->query
            ->shouldReceive('build')
            ->andThrow('Exception', 'Build method throw exception', 500)
        ;

        $this->assertFalse($this->repo->deleteCard(
            $this->payplug_card['id_customer'],
            $this->payplug_card['id_payplug_card']
        ));
    }

    public function testWhenCardCantBeDeletedFromDataBase()
    {
        $this->repo
            ->shouldReceive([
                'getCard' => $this->payplug_card,
                'isValidExpiration' => true,
                'deleteCardFromAPI' => true,
            ])
        ;

        $this->query
            ->shouldReceive([
                'delete' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => false,
            ])
        ;

        $this->assertFalse($this->repo->deleteCard(
            $this->payplug_card['id_customer'],
            $this->payplug_card['id_payplug_card']
        ));
    }

    public function testWhenCardIsDeletedFromDataBase()
    {
        $this->repo
            ->shouldReceive([
                'getCard' => $this->payplug_card,
                'isValidExpiration' => true,
                'deleteCardFromAPI' => true,
            ])
        ;

        $this->query
            ->shouldReceive([
                'delete' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => true,
            ])
        ;

        $this->assertTrue($this->repo->deleteCard(
            $this->payplug_card['id_customer'],
            $this->payplug_card['id_payplug_card']
        ));
    }

    public function testWhenCardExpired()
    {
        $this->repo
            ->shouldReceive([
                'getCard' => $this->payplug_card,
                'isValidExpiration' => false,
            ])
        ;

        $this->query
            ->shouldReceive([
                'delete' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => true,
            ])
        ;

        $this->assertTrue($this->repo->deleteCard(
            $this->payplug_card['id_customer'],
            $this->payplug_card['id_payplug_card']
        ));
    }
}
