<?php

namespace PayPlug\tests\models\classes\paymentMethod\AmexPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\AmexPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseAmexPaymentMethod extends BasePaymentMethod
{
    public function setUp()
    {
        parent::setUp();

        $this->parent = $this->class;
        $this->class = \Mockery::mock(AmexPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->configuration->shouldReceive('getValue')
            ->with('multi_account')
            ->andReturn(json_encode([]))
            ->byDefault();
    }
}
