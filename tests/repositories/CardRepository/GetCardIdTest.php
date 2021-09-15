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
final class GetCardIdTest extends BaseCardRepository
{
    private $customerId;
    private $payplugCardId;
    private $companyId;
    private $cards;

    public function setUp()
    {
        parent::setUp();

        $this->customerId = 42;
        $this->payplugCardId = 'pay_id';
        $this->companyId = 123;
        $this->cards = [[
            'id_card' => 42,
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
        // invalid int $customerId
        yield [null, 'I am a string!', 42];
        yield [false, 'I am a string!', 42];
        yield ['I am a string!', 'I am a string!', 42];
        yield [['key'=>'value'], 'I am a string!', 42];

        // invalid string $payplugCardId
        yield [42, null, 42];
        yield [42, false, 42];
        yield [42, 42, 42];
        yield [42, ['key'=>'value'], 42];

        // invalid int $companyId
        yield [42, 'I am a string!', null];
        yield [42, 'I am a string!', false];
        yield [42, 'I am a string!', 'I am a string!'];
        yield [42, 'I am a string!', ['key'=>'value']];
    }

    /**
     * @dataProvider invalidDataProvider
     * @param $customerId
     * @param $payplugCardId
     * @param $companyId
     */
    public function testWithInvalidParams($customerId, $payplugCardId, $companyId)
    {
        $this->assertFalse($this->repo->getCardId($customerId, $payplugCardId, $companyId));
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

        $this->assertFalse($this->repo->getCardId($this->customerId, $this->payplugCardId, $this->companyId));
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

        $this->assertFalse($this->repo->getCardId($this->customerId, $this->payplugCardId, $this->companyId));
    }

    public function testWithCardsFound()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => $this->cards
            ]);

        $this->assertSame(
            42,
            $this->repo->getCardId($this->customerId, $this->payplugCardId, $this->companyId)
        );
    }
}
