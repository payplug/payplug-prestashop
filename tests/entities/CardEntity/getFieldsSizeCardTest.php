<?php

use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetFieldsSizeCardTest extends TestCase
{
    protected $card;
    protected $fieldsSize;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->fieldsSize = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->card->setFieldsSize($this->fieldsSize);
    }

    public function testReturnFieldsSize()
    {
        $this->assertSame(
            $this->fieldsSize,
            $this->card->getFieldsSize()
        );
    }

    public function testFieldsSizeIsArray()
    {
        $this->assertTrue(
            is_array($this->card->getFieldsSize())
        );
    }

    public function testFieldsSizeIsNotEmpty()
    {
        $this->assertFalse(
            empty($this->card->getFieldsSize())
        );
    }
}
