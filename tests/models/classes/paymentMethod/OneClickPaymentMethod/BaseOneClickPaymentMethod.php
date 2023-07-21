<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneClickPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\OneClickPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseOneClickPaymentMethod extends BasePaymentMethod
{
    protected function setUp()
    {
        parent::setUp();

        $this->classe = \Mockery::mock(OneClickPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
