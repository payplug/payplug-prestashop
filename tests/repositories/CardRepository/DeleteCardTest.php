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
        yield [['key'=>'value'], 42];

        // invalid int $id_payplug_card
        yield [42, null];
        yield [42, false];
        yield [42, 'I am a string!'];
        yield [42, ['key'=>'value']];
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

    public function testWhenNoCardFound()
    {
        $this->repo
            ->shouldReceive([
                'getCard' => false
            ]);

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
            ]);

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
            ]);

        $this->query
            ->shouldReceive([
                'delete' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => false
            ]);

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
            ]);

        $this->query
            ->shouldReceive([
                'delete' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => true
            ]);

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
            ]);

        $this->query
            ->shouldReceive([
                'delete' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => true
            ]);

        $this->assertTrue($this->repo->deleteCard(
            $this->payplug_card['id_customer'],
            $this->payplug_card['id_payplug_card']
        ));
    }
}
