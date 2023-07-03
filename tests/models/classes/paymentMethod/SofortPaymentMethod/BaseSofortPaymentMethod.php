<?php

namespace PayPlug\tests\models\classes\paymentMethod\SofortPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\SofortPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseSofortPaymentMethod extends BasePaymentMethod
{
    protected function setUp()
    {
        parent::setUp();

        $this->classe = \Mockery::mock(SofortPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
