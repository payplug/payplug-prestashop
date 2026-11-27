<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\ApplepayPaymentMethod;
use PayPlug\tests\mock\CarrierMock;
use PayPlug\tests\mock\CountryMock;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseApplepayPaymentMethod extends BasePaymentMethod
{
    protected $country_adapter;
    protected $domain_association_action;

    public function setUp(): void
    {
        parent::setUp();

        $this->helpers['amount']->shouldReceive('validateAmount')
            ->andReturn([
                'result' => true,
                'message' => '',
            ]);
        $this->carrier_adapter->shouldReceive([
            'get' => CarrierMock::get(),
        ]);
        $this->country_adapter->shouldReceive([
            'get' => CountryMock::get(),
        ]);
        $this->language_helper->shouldReceive([
            'getIsoFromCodeLang' => 'fr',
        ]);

        $this->domain_association_action = \Mockery::mock('ApplePayDomainAssociationAction');
        $this->module
            ->shouldReceive('getService')
            ->with('payplug.action.apple_pay_domain_association')
            ->andReturn($this->domain_association_action);

        $this->class = \Mockery::mock(ApplepayPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
