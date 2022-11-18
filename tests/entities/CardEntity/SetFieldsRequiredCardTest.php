<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetFieldsRequiredCardTest extends TestCase
{
    protected $card;
    protected $fieldsRequired;
    protected $fieldsRequiredAlt;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->fieldsRequired = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->fieldsRequiredAlt = [
            'keyA' => 'valueA',
            'keyB' => 'valueB',
            'keyC' => 8,
        ];
        $this->card->setFieldsRequired($this->fieldsRequired);
    }

    public function testUpdateFieldsRequired()
    {
        $this->card->setFieldsRequired($this->fieldsRequiredAlt);
        $this->assertSame(
            $this->fieldsRequiredAlt,
            $this->card->getFieldsRequired()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->card->setFieldsRequired($this->fieldsRequiredAlt)
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
        $this->card->setFieldsRequired('wrong_parameter');
    }
}
