<?php

namespace PayPlug\tests\actions\ScalapayWidgetAction;

/**
 * @group unit
 * @group action
 * @group scalapay_widget_action
 */
class getLocaleTest extends BaseScalapayWidgetAction
{
    public function localeProvider()
    {
        yield ['fr', 'fr'];

        yield ['FR', 'fr'];

        yield ['it', 'it'];

        yield ['de', 'de'];

        yield ['es', 'es'];

        yield ['nl', 'nl'];

        yield ['pt', 'pt'];

        yield ['en', 'en'];

        yield ['gb', 'en'];

        yield ['pl', 'en'];

        yield ['', 'en'];
    }

    /**
     * @dataProvider localeProvider
     *
     * @param string $iso_code
     * @param string $expected
     */
    public function testTheShopLanguageGivesTheLocale($iso_code, $expected)
    {
        $this->context->language->iso_code = $iso_code;

        $this->assertSame($expected, $this->action->getLocale());
    }

    public function testMissingLanguageFallsBackToEnglish()
    {
        unset($this->context->language);

        $this->assertSame('en', $this->action->getLocale());
    }
}
