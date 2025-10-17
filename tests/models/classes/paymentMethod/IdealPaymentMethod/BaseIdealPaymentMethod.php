<?php

namespace PayPlug\tests\models\classes\paymentMethod\IdealPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\IdealPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseIdealPaymentMethod extends BasePaymentMethod
{
    public function setUp()
    {
        parent::setUp();

        $this->class = \Mockery::mock(IdealPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
