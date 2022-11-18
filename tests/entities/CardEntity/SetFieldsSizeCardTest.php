<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetFieldsSizeCardTest extends TestCase
{
    protected $card;
    protected $fieldsSize;
    protected $fieldsSizeAlt;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->fieldsSize = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->fieldsSizeAlt = [
            'keyA' => 'valueA',
            'keyB' => 'valueB',
            'keyC' => 8,
        ];
        $this->card->setFieldsSize($this->fieldsSize);
    }

    public function testUpdateFieldsSize()
    {
        $this->card->setFieldsSize($this->fieldsSizeAlt);
        $this->assertSame(
            $this->fieldsSizeAlt,
            $this->card->getFieldsSize()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->card->setFieldsSize($this->fieldsSizeAlt)
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
        $this->card->setFieldsSize('wrong_parameter');
    }
}
