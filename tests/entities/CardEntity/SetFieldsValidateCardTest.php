<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetFieldsValidateCardTest extends TestCase
{
    protected $card;
    protected $fieldsValidate;
    protected $fieldsValidateAlt;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->fieldsValidate = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->fieldsValidateAlt = [
            'keyA' => 'valueA',
            'keyB' => 'valueB',
            'keyC' => 8,
        ];
        $this->card->setFieldsValidate($this->fieldsValidate);
    }

    public function testUpdateFieldsValidate()
    {
        $this->card->setFieldsValidate($this->fieldsValidateAlt);
        $this->assertSame(
            $this->fieldsValidateAlt,
            $this->card->getFieldsValidate()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->card->setFieldsValidate($this->fieldsValidateAlt)
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
        $this->card->setFieldsValidate('wrong_parameter');
    }
}
