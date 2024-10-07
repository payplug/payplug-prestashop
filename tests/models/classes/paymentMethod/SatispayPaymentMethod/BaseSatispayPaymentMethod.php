<?php

namespace PayPlug\tests\models\classes\paymentMethod\SatispayPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\SatispayPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseSatispayPaymentMethod extends BasePaymentMethod
{
    public function setUp()
    {
        parent::setUp();

        $this->class = \Mockery::mock(SatispayPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
