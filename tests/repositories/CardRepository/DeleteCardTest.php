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
final class DeleteCardTest extends BaseCardRepository
{
    private $id_customer;
    private $id_card;
    private $id_payplug_card;
    private $card;

    public function setUp()
    {
        parent::setUp();

        $this->id_customer = 42;
        $this->id_card = 123;
        $this->id_payplug_card = 'pay_id';
        $this->card = \Mockery::mock('alias:Payplug\Card');
    }

    public function invalidDataProvider()
    {
        $object = new \stdClass();

        yield [null, null];
        yield ['I am a string!', null];
        yield ['I am a string!', 'I am a string!'];
        yield ['I am a string!', 123];
        yield ['I am a string!', $object];
        yield ['I am a string!', ['wrong_parameters' => 'value']];
        yield [null, ['wrong_parameters' => 'value']];
        yield ['I am a string!', ['wrong_parameters' => 'value']];
        yield [123, ['wrong_parameters' => 'value']];
        yield [$object, ['wrong_parameters' => 'value']];
        yield [['wrong_parameters' => 'value'], ['wrong_parameters' => 'value']];
    }

    /**
     * @dataProvider invalidDataProvider
     * @param $id_customer
     * @param $id_payplug_card
     */
    public function testWithInvalidParams($id_customer, $id_payplug_card)
    {
        $this->assertFalse($this->repo->deleteCard($id_customer, $id_payplug_card));
    }

    public function testWhenNoCardIdFound()
    {
        $this->repo
            ->shouldReceive([
                'getCardId' => false
            ]);

        $this->assertTrue($this->repo->deleteCard($this->id_customer, $this->id_payplug_card));
    }

    public function testWhenAPIThrowingConfigurationNotSetException()
    {
        $this->repo
            ->shouldReceive([
                'getCardId' => $this->id_card
            ]);

        $this->card->shouldReceive('delete')
            ->andThrow('Payplug\Exception\ConfigurationNotSetException', 'An error occurred', 500);

        $this->assertTrue($this->repo->deleteCard($this->id_customer, $this->id_payplug_card));
    }

    public function testWhenAPIThrowingNotFoundException()
    {
        $this->repo
            ->shouldReceive([
                'getCardId' => $this->id_card
            ]);

        $this->card->shouldReceive('delete')
            ->andThrow('Payplug\Exception\NotFoundException', 'Card not found', 404);

        $this->assertTrue($this->repo->deleteCard($this->id_customer, $this->id_payplug_card));
    }

    public function testWhenAPIReturnError()
    {
        $this->repo
            ->shouldReceive([
                'getCardId' => $this->id_card
            ]);

        $this->card->shouldReceive([
            'delete' => [
                'httpResponse' => [
                    'object' => 'error'
                ]
            ]
        ]);

        $this->assertFalse($this->repo->deleteCard($this->id_customer, $this->id_payplug_card));
    }

    public function testWhenDeleteCardFromDataBaseThrowingException()
    {
        $this->repo
            ->shouldReceive([
                'getCardId' => $this->id_card
            ]);

        $this->card->shouldReceive([
            'delete' => [
                'httpResponse' => [
                    'object' => 'success'
                ]
            ]
        ]);

        $this->query
            ->shouldReceive([
                'delete' => $this->query,
                'from' => $this->query,
                'where' => $this->query
            ]);

        $this->query
            ->shouldReceive('build')
            ->andThrow('Exception', 'Build method throw exception', 500);

        $this->assertFalse($this->repo->deleteCard($this->id_customer, $this->id_payplug_card));
    }

    public function testWhenCardCantBeDeletedFromDataBase()
    {
        $this->repo
            ->shouldReceive([
                'getCardId' => $this->id_card
            ]);

        $this->card->shouldReceive([
            'delete' => [
                'httpResponse' => [
                    'object' => 'success'
                ]
            ]
        ]);

        $this->query
            ->shouldReceive([
                'delete' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => false
            ]);

        $this->assertFalse($this->repo->deleteCard($this->id_customer, $this->id_payplug_card));
    }

    public function testWhenCardIsDeletedFromDataBase()
    {
        $this->repo
            ->shouldReceive([
                'getCardId' => $this->id_card
            ]);

        $this->card->shouldReceive([
            'delete' => [
                'httpResponse' => [
                    'object' => 'success'
                ]
            ]
        ]);

        $this->query
            ->shouldReceive([
                'delete' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => true
            ]);

        $this->assertTrue($this->repo->deleteCard($this->id_customer, $this->id_payplug_card));
    }
}
