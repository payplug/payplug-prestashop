<?php

namespace PayPlug\tests\models\classes\paymentMethod\MybankPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\MybankPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseMybankPaymentMethod extends BasePaymentMethod
{
    protected function setUp()
    {
        parent::setUp();

        $this->classe = \Mockery::mock(MybankPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
