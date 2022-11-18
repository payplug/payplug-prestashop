<?php

use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetIdCompanyCardTest extends TestCase
{
    protected $card;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->card->setIdCompany(42);
    }

    public function testReturnIdCompany()
    {
        $this->assertSame(
            42,
            $this->card->getIdCompany()
        );
    }

    public function testIdCompanyIsAnInt()
    {
        $this->assertTrue(
            is_int($this->card->getIdCompany())
        );
    }
}
