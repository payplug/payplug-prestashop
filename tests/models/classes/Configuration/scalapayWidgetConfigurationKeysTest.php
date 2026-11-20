<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group class
 * @group configuration_class
 */
class scalapayWidgetConfigurationKeysTest extends BaseConfiguration
{
    public function switchProvider()
    {
        yield ['scalapay_product_widget', 'PAYPLUG_SCALAPAY_PRODUCT_WIDGET'];

        yield ['scalapay_cart_widget', 'PAYPLUG_SCALAPAY_CART_WIDGET'];

        yield ['scalapay_checkout_widget', 'PAYPLUG_SCALAPAY_CHECKOUT_WIDGET'];
    }

    /**
     * @dataProvider switchProvider
     *
     * @param string $key
     * @param string $name
     */
    public function testWidgetSwitchesAreOnByDefault($key, $name)
    {
        $this->assertSame(1, $this->class->getDefault($key));
        $this->assertSame('integer', $this->class->getType($key));
        $this->assertSame($name, $this->class->getName($key));
        $this->assertSame(1, $this->class->get($key)['setConf']);
    }

    public function testMerchantTokenDefaultsToAnEmptyString()
    {
        $this->assertSame('', $this->class->getDefault('scalapay_merchant_token'));
        $this->assertSame('string', $this->class->getType('scalapay_merchant_token'));
        $this->assertSame('PAYPLUG_SCALAPAY_MERCHANT_TOKEN', $this->class->getName('scalapay_merchant_token'));
        $this->assertSame(1, $this->class->get('scalapay_merchant_token')['setConf']);
    }

    public function testAmountSelectorsDefaultToAnEmptyJsonObject()
    {
        $this->assertSame('{}', $this->class->getDefault('scalapay_amount_selectors'));
        $this->assertSame('string', $this->class->getType('scalapay_amount_selectors'));
        $this->assertSame('PAYPLUG_SCALAPAY_AMOUNT_SELECTORS', $this->class->getName('scalapay_amount_selectors'));
        $this->assertSame(1, $this->class->get('scalapay_amount_selectors')['setConf']);
    }

    public function testTheWidgetKeysAreSeededOnInstall()
    {
        $seeded = [];
        $this->configuration->shouldReceive('updateValue')
            ->andReturnUsing(function ($name, $value) use (&$seeded) {
                $seeded[$name] = $value;

                return true;
            });

        $this->assertTrue($this->class->initialize());

        $this->assertSame('1', $seeded['PAYPLUG_SCALAPAY_PRODUCT_WIDGET']);
        $this->assertSame('1', $seeded['PAYPLUG_SCALAPAY_CART_WIDGET']);
        $this->assertSame('1', $seeded['PAYPLUG_SCALAPAY_CHECKOUT_WIDGET']);
        $this->assertSame('', $seeded['PAYPLUG_SCALAPAY_MERCHANT_TOKEN']);
        $this->assertSame('{}', $seeded['PAYPLUG_SCALAPAY_AMOUNT_SELECTORS']);
    }
}
