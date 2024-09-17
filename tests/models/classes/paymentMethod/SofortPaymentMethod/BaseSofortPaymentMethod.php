<?php

namespace PayPlug\tests\models\classes\paymentMethod\SofortPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\SofortPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseSofortPaymentMethod extends BasePaymentMethod
{
    public function setUp()
    {
        parent::setUp();

        $this->class = \Mockery::mock(SofortPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
