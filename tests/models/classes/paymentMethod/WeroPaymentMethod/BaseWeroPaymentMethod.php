<?php

namespace PayPlug\tests\models\classes\paymentMethod\WeroPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\WeroPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseWeroPaymentMethod extends BasePaymentMethod
{
    public function setUp()
    {
        parent::setUp();

        $this->class = \Mockery::mock(WeroPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
