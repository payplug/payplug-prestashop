<?php

namespace PayPlug\tests\models\classes\paymentMethod\BancontactPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\BancontactPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseBancontactPaymentMethod extends BasePaymentMethod
{
    protected function setUp()
    {
        parent::setUp();

        $this->classe = \Mockery::mock(BancontactPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
