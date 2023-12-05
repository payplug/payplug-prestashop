<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\ApplepayPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseApplepayPaymentMethod extends BasePaymentMethod
{
    protected function setUp()
    {
        parent::setUp();

        $this->helpers['amount']
            ->shouldReceive('validateAmount')
            ->andReturn([
                'result' => true,
                'message' => '',
            ]);

        $this->classe = \Mockery::mock(ApplepayPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
