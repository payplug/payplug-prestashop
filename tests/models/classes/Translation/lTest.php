<?php

namespace PayPlug\tests\models\classes\Translation;

/**
 * @group unit
 * @group class
 * @group translation_class
 */
class lTest extends BaseTranslation
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $string
     */
    public function testWhenGivenStringWithInvalidStringFormat($string)
    {
        $template = 'alias';
        $this->assertSame(
            '',
            $this->class->l($string, $template)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $template
     */
    public function testWhenGivenTemplateWithInvalidStringFormat($template)
    {
        $string = 'translation.key';
        $this->assertSame(
            '',
            $this->class->l($string, $template)
        );
    }

    public function testWhenGivenTranslationDifferFromKey()
    {
        $template = 'alias';
        $string = 'translation.key';

        $transation_adapter = \Mockery::mock('TranslationAdapter');
        $translation = 'transation';
        $transation_adapter->shouldReceive([
            'trans' => $translation,
        ]);

        $this->plugin->shouldReceive([
            'getTranslationAdapter' => $transation_adapter,
        ]);

        $this->assertSame(
            $translation,
            $this->class->l($string, $template)
        );
    }

    public function testWhenGivenTranslationIsEqualToKey()
    {
        $template = 'alias';
        $string = 'translation.key';

        $transation_adapter = \Mockery::mock('TranslationAdapter');
        $transation_adapter->shouldReceive([
            'trans' => $string,
        ]);
        $this->plugin->shouldReceive([
            'getTranslationAdapter' => $transation_adapter,
        ]);

        $translation = 'default transation';
        $this->class->shouldReceive([
            'getDefaultTranslation' => $translation,
        ]);

        $this->assertSame(
            $translation,
            $this->class->l($string, $template)
        );
    }
}
