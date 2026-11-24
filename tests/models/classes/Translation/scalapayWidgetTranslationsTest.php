<?php

namespace PayPlug\tests\models\classes\Translation;

/**
 * @group unit
 * @group class
 * @group translation_class
 */
class scalapayWidgetTranslationsTest extends BaseTranslation
{
    public function setUp(): void
    {
        parent::setUp();

        $this->class->shouldReceive('l')
            ->andReturnUsing(function ($string) {
                return $string;
            });
    }

    public function testTheWidgetLabelsAreDeclared()
    {
        $translations = $this->class->getPaymentMethodsTranslations()['scalapay']['widget'];

        foreach (['checkout', 'product', 'cart'] as $page) {
            $this->assertSame('paymentmethods.scalapay.widget.' . $page . '.title', $translations[$page]['title']);
            $this->assertSame('paymentmethods.scalapay.widget.' . $page . '.description', $translations[$page]['description']);
        }
        $this->assertSame(['checkout', 'product', 'cart'], array_keys($translations));
        $this->assertArrayNotHasKey('scalapay', $this->class->getModalTranslations());
    }

    public function localeProvider()
    {
        foreach (['de', 'en', 'es', 'fr', 'gb', 'it', 'nl', 'pt'] as $locale) {
            yield [$locale];
        }
    }

    /**
     * @dataProvider localeProvider
     *
     * @param string $locale
     */
    public function testEveryLocaleTranslatesTheWidgetKeys($locale)
    {
        $_MODULE = [];

        include dirname(__FILE__) . '/../../../../translations/' . $locale . '.php';

        foreach ($this->getKeys() as $key) {
            $module_key = '<{payplug}prestashop>translation_' . md5($key);
            $this->assertArrayHasKey($module_key, $_MODULE, $locale . ': ' . $key);
            $this->assertNotSame('', $_MODULE[$module_key], $locale . ': ' . $key);
        }
    }

    public function testTheTranslationCsvHasEveryWidgetKey()
    {
        $csv = file_get_contents(dirname(__FILE__) . '/../../../../dev/dist/payplug/translations.csv');

        foreach ($this->getKeys() as $key) {
            $this->assertStringContainsString('<{payplug}prestashop>translation_' . md5($key) . ';' . $key . ';', $csv, $key);
        }
    }

    /**
     * @return string[]
     */
    private function getKeys()
    {
        $keys = [];
        array_walk_recursive(
            $this->class->getPaymentMethodsTranslations()['scalapay']['widget'],
            function ($key) use (&$keys) {
                $keys[] = $key;
            }
        );

        return $keys;
    }
}
