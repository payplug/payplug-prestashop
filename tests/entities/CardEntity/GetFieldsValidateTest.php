<?php

use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetFieldsValidateTest extends TestCase
{
    protected $card;
    protected $fieldsValidate;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->fieldsValidate = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->card->setFieldsValidate($this->fieldsValidate);
    }

    public function testReturnFieldsValidate()
    {
        $this->assertSame(
            $this->fieldsValidate,
            $this->card->getFieldsValidate()
        );
    }

    public function testFieldsValidateIsArray()
    {
        $this->assertTrue(
            is_array($this->card->getFieldsValidate())
        );
    }

    public function testFieldsValidateIsNotEmpty()
    {
        $this->assertFalse(
            empty($this->card->getFieldsValidate())
        );
    }
}
