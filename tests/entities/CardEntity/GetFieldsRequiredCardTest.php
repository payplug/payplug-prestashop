<?php

use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetFieldsRequiredCardTest extends TestCase
{
    protected $card;
    protected $fieldsRequired;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->fieldsRequired = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->card->setFieldsRequired($this->fieldsRequired);
    }

    public function testReturnFieldsRequired()
    {
        $this->assertSame(
            $this->fieldsRequired,
            $this->card->getFieldsRequired()
        );
    }

    public function testFieldsRequiredIsArray()
    {
        $this->assertTrue(
            is_array($this->card->getFieldsRequired())
        );
    }

    public function testFieldsRequiredIsntEmpty()
    {
        $this->assertFalse(
            empty($this->card->getFieldsRequired())
        );
    }
}
