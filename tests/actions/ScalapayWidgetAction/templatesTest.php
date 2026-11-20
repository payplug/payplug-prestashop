<?php

namespace PayPlug\tests\actions\ScalapayWidgetAction;

use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group action
 * @group scalapay_widget_action
 */
class templatesTest extends TestCase
{
    public function testTheScriptTemplateLoadsTheWidgetAsAModule()
    {
        $this->assertSame(
            '<script type="module" src="{$scalapay_widget_script_url|escape:\'htmlall\':\'UTF-8\'}"></script>',
            $this->getMarkup('script.tpl')
        );
    }

    public function testTheWidgetTemplateOnlyHasTheFourScalapayAttributes()
    {
        $this->assertSame(
            '<scalapay-widget'
            . ' merchant-token="{$scalapay_merchant_token|escape:\'htmlall\':\'UTF-8\'}"'
            . ' type="{$scalapay_type|escape:\'htmlall\':\'UTF-8\'}"'
            . ' amount-selectors="{$scalapay_amount_selectors|escape:\'htmlall\':\'UTF-8\'}"'
            . ' locale="{$scalapay_locale|escape:\'htmlall\':\'UTF-8\'}"'
            . '></scalapay-widget>',
            $this->getMarkup('widget.tpl')
        );
    }

    /**
     * @param string $file
     *
     * @return string the template without its license header
     */
    private function getMarkup($file)
    {
        $content = file_get_contents(dirname(__DIR__, 3) . '/views/templates/hook/scalapay/' . $file);

        return trim(preg_replace('/^\{\*.*?\*\}/s', '', $content));
    }
}
