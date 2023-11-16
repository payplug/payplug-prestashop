<?php

namespace PayPlug\tests\models\classes\paymentMethod\AmexPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\AmexPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseAmexPaymentMethod extends BasePaymentMethod
{
    protected function setUp()
    {
        parent::setUp();

        $this->parent = $this->classe;
        $this->classe = \Mockery::mock(AmexPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
