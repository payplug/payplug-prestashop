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
        yield [['key' => 'value']];
    }

    /**
     * @dataProvider invalidDataProvider
     *
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
                'where' => $this->query,
            ])
        ;

        $this->query
            ->shouldReceive('build')
            ->andThrow('Exception', 'Build method throw exception', 500)
        ;

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
                'build' => [],
            ])
        ;

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
                'build' => [$this->payplug_card],
            ])
        ;

        $this->assertSame(
            $this->payplug_card,
            $this->repo->getCard($this->payplug_card['id_payplug_card'])
        );
    }
}
