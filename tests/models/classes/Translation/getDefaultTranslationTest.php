<?php

namespace PayPlug\tests\models\classes\Translation;

/**
 * @group unit
 * @group classes
 * @group translation_classes
 *
 * @runTestsInSeparateProcesses
 */
class getDefaultTranslationTest extends BaseTranslation
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
            $this->classe->getDefaultTranslation($string, $template)
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
            $this->classe->getDefaultTranslation($string, $template)
        );
    }

    public function testWhenDefaultTranslationsIsEmpty()
    {
        $string = 'translation.key';
        $template = 'alias';
        $this->classe->default_translations = [];
        $this->assertSame(
            '',
            $this->classe->getDefaultTranslation($string, $template)
        );
    }

    public function testWhenNoDefaultTranslationsForGivenString()
    {
        $string = 'translation.key';
        $template = 'alias';
        $this->classe->default_translations = [
            'key' => 'translation',
        ];
        $this->assertSame(
            $string,
            $this->classe->getDefaultTranslation($string, $template)
        );
    }

    public function testWhenDefaultTranslationIsReturn()
    {
        $string = 'translation.key';
        $template = 'alias';
        $expected_key = '<{' . $this->dependencies->name . '}prestashop>' . strtolower($template) . '_' . md5($string);
        $expected_translation = 'expected translation';
        $this->classe->default_translations = [
            $expected_key => $expected_translation,
        ];

        $this->assertSame(
            $expected_translation,
            $this->classe->getDefaultTranslation($string, $template)
        );
    }
}
