<?php

namespace PayPlug\tests\models\classes\paymentMethod\BancontactPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\BancontactPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseBancontactPaymentMethod extends BasePaymentMethod
{
    public function setUp()
    {
        parent::setUp();

        $this->class = \Mockery::mock(BancontactPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
