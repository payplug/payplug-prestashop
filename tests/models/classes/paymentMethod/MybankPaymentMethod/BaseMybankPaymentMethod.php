<?php

namespace PayPlug\tests\models\classes\paymentMethod\MybankPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\MybankPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseMybankPaymentMethod extends BasePaymentMethod
{
    public function setUp()
    {
        parent::setUp();

        $this->class = \Mockery::mock(MybankPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
