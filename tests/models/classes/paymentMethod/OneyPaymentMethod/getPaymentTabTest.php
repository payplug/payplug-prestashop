<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 * @group debug
 */
class getPaymentTabTest extends BaseOneyPaymentMethod
{
    public $expected_tab;
    public $oney;
    public $oney_schedule;
    public $oney_context;

    public function setUp()
    {
        parent::setUp();
        $this->oney_schedule = '2';
        $this->oney_context = 'oney_context';
        $this->class->shouldReceive([
            'isValidOneyCartQty' => [
                'result' => true,
            ],
            'isValidOneyAddresses' => [
                'result' => true,
            ],
            'isValidOneyAmount' => [
                'result' => true,
            ],
            'getOneyPaymentContext' => $this->oney_context,
        ]);

        $this->configuration->shouldReceive('getValue')
            ->with('PS_TAX')
            ->andReturn(42);
        $this->tools_adapter->shouldReceive('tool')
            ->withArgs(['getValue', 'payplugOney_type'])
            ->andReturn(2);
        $this->tools_adapter->shouldReceive('tool')
            ->withArgs(['getValue', 'io'])
            ->andReturn(1);
        $this->helpers['cookies']->shouldReceive([
            'setPaymentErrorsCookie' => true,
        ]);
    }

    public function testWhenParentMethodReturnEmptyArray()
    {
        $this->class->shouldReceive([
            'getDefaultPaymentTab' => [],
        ]);
        $this->assertSame(
            [],
            $this->class->getPaymentTab()
        );
    }

    public function testWhenCurrentCartIsntElligible()
    {
        $this->class->shouldReceive([
            'getDefaultPaymentTab' => $this->default_payment_tab,
        ]);

        $this->validators['payment']->shouldReceive([
            'isOneyElligible' => [
                'result' => false,
                'code' => 'product_quantity',
            ],
        ]);

        $this->assertSame(
            [],
            $this->class->getPaymentTab()
        );
    }

    public function testWhenRequiredFieldsIsNeeded()
    {
        $this->class->shouldReceive([
            'getDefaultPaymentTab' => $this->default_payment_tab,
            'hasOneyRequiredFields' => true,
        ]);
        $this->validators['payment']->shouldReceive([
            'isOneyElligible' => [
                'result' => true,
            ],
        ]);
        $this->helpers['cookies']->shouldReceive([
            'getPaymentDataCookie' => true,
        ]);

        $this->assertSame(
            [],
            $this->class->getPaymentTab()
        );
    }

    public function testPaymentTabIsReturned()
    {
        $this->class->shouldReceive([
            'getDefaultPaymentTab' => $this->default_payment_tab,
            'hasOneyRequiredFields' => false,
        ]);
        $this->validators['payment']->shouldReceive([
            'isOneyElligible' => [
                'result' => true,
            ],
        ]);

        $expected_tab = $this->default_payment_tab;
        $expected_tab['authorized_amount'] = $expected_tab['amount'];
        $expected_tab['force_3ds'] = false;
        $expected_tab['auto_capture'] = true;
        $expected_tab['payment_method'] = 'oney_' . $this->oney_schedule;
        $expected_tab['payment_context'] = $this->oney_context;
        $expected_tab['hosted_payment']['return_url'] = 'link';

        unset($expected_tab['allow_save_card'], $expected_tab['amount']);

        $this->assertSame(
            $expected_tab,
            $this->class->getPaymentTab()
        );
    }
}
