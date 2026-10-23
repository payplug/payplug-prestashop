<?php

namespace PayPlug\tests\models\classes\Translation;

/**
 * @group unit
 * @group class
 * @group translation_class
 */
class apmTranslationsTest extends BaseTranslation
{
    public function setUp(): void
    {
        parent::setUp();

        $this->class->shouldReceive('l')
            ->andReturnUsing(function ($string) {
                return $string;
            });
    }

    public function testBackOfficeApmLabelsAreDeclared()
    {
        $translations = $this->class->getPaymentMethodsTranslations()['apm'];

        $this->assertSame('paymentmethods.apm.thresholds.title', $translations['thresholds']['title']);
        $this->assertSame('paymentmethods.apm.thresholds.error.max.text', $translations['thresholds']['error']['max']);
        $this->assertSame('paymentmethods.apm.countries.title', $translations['countries']['title']);
        $this->assertSame('paymentmethods.apm.countries.warning', $translations['countries']['warning']);
        $this->assertSame('paymentmethods.apm.countries.help', $translations['countries']['help']);
        $this->assertSame('paymentmethods.apm.countries.empty', $translations['countries']['empty']);
        $this->assertSame('paymentmethods.apm.countries.removed', $translations['countries']['removed']);
    }

    public function testSaveErrorMessagesAreDeclared()
    {
        $translations = $this->class->getModalTranslations()['apm'];

        foreach (['amount_invalid', 'amount_out_of_range', 'max_not_above_min', 'country_not_allowed', 'no_config', 'submit'] as $key) {
            $this->assertSame('modal.apm.' . $key, $translations[$key]);
        }
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
    public function testEveryLocaleTranslatesTheApmKeys($locale)
    {
        $keys = array_merge(
            $this->flatten($this->class->getPaymentMethodsTranslations()['apm']),
            $this->flatten($this->class->getModalTranslations()['apm'])
        );
        $_MODULE = [];

        include dirname(__FILE__) . '/../../../../translations/' . $locale . '.php';

        foreach ($keys as $key) {
            $module_key = '<{payplug}prestashop>translation_' . md5($key);
            $this->assertArrayHasKey($module_key, $_MODULE, $locale . ': ' . $key);
            $this->assertNotSame('', $_MODULE[$module_key], $locale . ': ' . $key);
        }
    }

    /**
     * @dataProvider localeProvider
     *
     * @param string $locale
     */
    public function testEveryLocaleKeepsThePlaceholdersOfTheCountryMessages($locale)
    {
        $placeholders = [
            'paymentmethods.apm.countries.help' => ['%s'],
            'paymentmethods.apm.countries.empty' => ['%1$s', '%2$s'],
            'paymentmethods.apm.countries.removed' => ['%s'],
        ];
        $_MODULE = [];

        include dirname(__FILE__) . '/../../../../translations/' . $locale . '.php';

        foreach ($placeholders as $key => $expected) {
            $message = $_MODULE['<{payplug}prestashop>translation_' . md5($key)];
            foreach ($expected as $placeholder) {
                $this->assertSame(1, substr_count($message, $placeholder), $locale . ': ' . $key);
            }
        }
    }

    /**
     * @param array $translations
     *
     * @return string[]
     */
    private function flatten($translations)
    {
        $keys = [];
        foreach ($translations as $translation) {
            $keys = is_array($translation)
                ? array_merge($keys, $this->flatten($translation))
                : array_merge($keys, [$translation]);
        }

        return $keys;
    }
}
