<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneClickPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\OneClickPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseOneClickPaymentMethod extends BasePaymentMethod
{
    public function setUp(): void
    {
        parent::setUp();

        $this->class = \Mockery::mock(OneClickPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
