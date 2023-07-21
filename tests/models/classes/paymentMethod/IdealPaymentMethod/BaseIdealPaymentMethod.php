<?php

namespace PayPlug\tests\models\classes\paymentMethod\IdealPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\IdealPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseIdealPaymentMethod extends BasePaymentMethod
{
    protected function setUp()
    {
        parent::setUp();

        $this->classe = \Mockery::mock(IdealPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
