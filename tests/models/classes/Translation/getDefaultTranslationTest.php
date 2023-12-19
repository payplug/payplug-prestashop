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
        $default_lang = 'zh';
        $default_translations = [];
        $this->assertSame(
            '',
            $this->classe->getDefaultTranslation($string, $template, $default_translations, $default_lang)
        );
    }

    public function testWhenNoDefaultTranslationsForGivenString()
    {
        $string = 'translation.key';
        $template = 'alias';
        $default_translations = [
            'key' => 'translation',
        ];
        $this->assertSame(
            $string,
            $this->classe->getDefaultTranslation($string, $template, $default_translations)
        );
    }

    public function testWhenDefaultTranslationIsReturn()
    {
        $string = 'translation.key';
        $template = 'alias';
        $default_lang = 'zh';
        $expected_key = '<{' . $this->dependencies->name . '}prestashop>' . strtolower($template) . '_' . md5($string);
        $expected_translation = 'expected translation';
        $default_translations = [
            $expected_key => $expected_translation,
        ];

        $this->assertSame(
            $expected_translation,
            $this->classe->getDefaultTranslation($string, $template, $default_translations, $default_lang)
        );
    }
}
