<?php

namespace PayPlug\tests\models\classes\paymentMethod\SatispayPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\SatispayPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseSatispayPaymentMethod extends BasePaymentMethod
{
    protected function setUp()
    {
        parent::setUp();

        $this->classe = \Mockery::mock(SatispayPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
