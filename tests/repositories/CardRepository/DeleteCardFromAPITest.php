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
final class DeleteCardFromAPITest extends BaseCardRepository
{
    private $id_card;
    private $card;

    public function setUp()
    {
        parent::setUp();
        $this->card = \Mockery::mock('alias:Payplug\Card');
        $this->id_card = 'card_4242LoremIpsumSit42442';
    }

    public function invalidDataProvider()
    {
        // invalid string $id_customer
        yield [null];
        yield [false];
        yield [42];
        yield [['key'=>'value']];
    }

    /**
     * @dataProvider invalidDataProvider
     * @param $id_customer
     * @param $id_payplug_card
     */
    public function testWithInvalidParams($id_card)
    {
        $this->assertFalse($this->repo->deleteCardFromAPI($id_card));
    }

    public function testWhenAPIThrowingConfigurationNotSetException()
    {
        $this->dependencies->apiClass->shouldReceive([
            'deleteCard' => [
                'code' => 500,
                'result' => false,
                'message' => 'An error occured'
            ]
        ]);

        $this->assertTrue($this->repo->deleteCardFromAPI($this->id_card));
    }

    public function testWhenAPIThrowingNotFoundException()
    {
        $this->dependencies->apiClass->shouldReceive([
            'deleteCard' => [
                'code' => 404,
                'result' => false,
                'message' => 'Card not found'
            ]
        ]);

        $this->assertTrue($this->repo->deleteCardFromAPI($this->id_card));
    }

    public function testWhenAPIReturnError()
    {
        $this->dependencies->apiClass->shouldReceive([
            'deleteCard' => [
                'code' => 200,
                'result' => true,
                'resource' => [
                    'httpResponse' => [
                        'object' => 'error'
                    ]
                ]
            ]
        ]);

        $this->assertFalse($this->repo->deleteCardFromAPI($this->id_card));
    }

    public function testWhenAPIReturnSuccess()
    {
        $this->dependencies->apiClass->shouldReceive([
            'deleteCard' => [
                'code' => 200,
                'result' => true,
                'resource' => [
                    'httpResponse' => [
                        'object' => 'success'
                    ]
                ]
            ]
        ]);

        $this->assertTrue($this->repo->deleteCardFromAPI($this->id_card));
    }
}
