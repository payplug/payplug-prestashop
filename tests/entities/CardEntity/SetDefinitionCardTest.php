<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetDefinitionCardTest extends TestCase
{
    protected $card;
    protected $definition;
    protected $definition_alt;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->definition = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->definition_alt = [
            'keyA' => 'valueA',
            'keyB' => 'valueB',
            'keyC' => 8,
        ];
        $this->card->setDefinition($this->definition);
    }

    public function testUpdateDefinition()
    {
        $this->card->setDefinition($this->definition_alt);
        $this->assertSame(
            $this->definition_alt,
            $this->card->getDefinition()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->card->setDefinition($this->definition_alt)
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
        $this->card->setDefinition('wrong_parameter');
    }
}
