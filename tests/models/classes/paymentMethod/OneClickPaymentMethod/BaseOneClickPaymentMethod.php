<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneClickPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\OneClickPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseOneClickPaymentMethod extends BasePaymentMethod
{
    public function setUp()
    {
        parent::setUp();

        $this->class = \Mockery::mock(OneClickPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->configuration->shouldReceive('getValue')
            ->with('multi_account')
            ->andReturn(json_encode([]))
            ->byDefault();
    }
}
