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
final class GetCardTest extends BaseCardRepository
{
    private $payplug_card;

    public function setUp()
    {
        parent::setUp();
        $this->payplug_card = PayPlugCardMock::get();
    }

    public function invalidDataProvider()
    {
        // invalid int $payplugCardId
        yield [null];
        yield [false];
        yield ['wrong parameter'];
        yield [['key'=>'value']];
    }

    /**
     * @dataProvider invalidDataProvider
     * @param $customerId
     * @param $payplugCardId
     * @param $companyId
     */
    public function testWithInvalidParams($payplugCardId)
    {
        $this->assertFalse($this->repo->getCard($payplugCardId));
    }

    public function testWhenDataBaseThrowingException()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query
            ]);

        $this->query
            ->shouldReceive('build')
            ->andThrow('Exception', 'Build method throw exception', 500);

        $this->assertFalse($this->repo->getCard($this->payplug_card['id_payplug_card']));
    }

    public function testWithoutCardsFound()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => []
            ]);

        $this->assertFalse($this->repo->getCard($this->payplug_card['id_payplug_card']));
    }

    public function testWithCardFound()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => [$this->payplug_card]
            ]);

        $this->assertSame(
            $this->payplug_card,
            $this->repo->getCard($this->payplug_card['id_payplug_card'])
        );
    }
}
