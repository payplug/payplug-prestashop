<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\ApplepayPaymentMethod;
use PayPlug\tests\mock\CarrierMock;
use PayPlug\tests\mock\CountryMock;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseApplepayPaymentMethod extends BasePaymentMethod
{
    protected $country_adapter;

    protected function setUp()
    {
        parent::setUp();

        $this->helpers['amount']
            ->shouldReceive('validateAmount')
            ->andReturn([
                'result' => true,
                'message' => '',
            ]);

        $this->carrier_adapter
            ->shouldReceive([
                'get' => CarrierMock::get(),
            ]);
        $this->country_adapter
            ->shouldReceive([
                'get' => CountryMock::get(),
            ]);

        $this->classe = \Mockery::mock(ApplepayPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
