<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetAllowedBrandCardTest extends TestCase
{
    protected $card;
    protected $allowedBranb;
    protected $allowedBranbAlt;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->allowedBranb = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->allowedBranbAlt = [
            'keyA' => 'valueA',
            'keyB' => 'valueB',
            'keyC' => 8,
        ];
        $this->card->setAllowedBrand($this->allowedBranb);
    }

    public function testUpdateAllowedBrand()
    {
        $this->card->setAllowedBrand($this->allowedBranbAlt);
        $this->assertSame(
            $this->allowedBranbAlt,
            $this->card->getAllowedBrand()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->card->setAllowedBrand($this->allowedBranbAlt)
        );
    }

    /**
     * @group entity_exception
     * @group card_exception
     * @group card_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotAnArray()
    {
        $this->expectException(BadParameterException::class);
        $this->card->setAllowedBrand('wrong_parameter');
    }
}
