<?php

namespace PayPlug\tests\models\classes\paymentMethod\StandardPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\StandardPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseStandardPaymentMethod extends BasePaymentMethod
{
    public function setUp()
    {
        parent::setUp();

        $this->class = \Mockery::mock(StandardPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->class->set('translation', $this->translation->getPaymentMethodsTranslations());
    }
}
