<?php

namespace PayPlug\tests\models\classes\paymentMethod\GiropayPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\GiropayPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseGiropayPaymentMethod extends BasePaymentMethod
{
    protected function setUp()
    {
        parent::setUp();

        $this->classe = \Mockery::mock(GiropayPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
