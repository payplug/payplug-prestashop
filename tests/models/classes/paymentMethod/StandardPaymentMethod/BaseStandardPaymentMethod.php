<?php

namespace PayPlug\tests\models\classes\paymentMethod\StandardPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\StandardPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseStandardPaymentMethod extends BasePaymentMethod
{
    protected function setUp()
    {
        parent::setUp();

        $this->classe = \Mockery::mock(StandardPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->classe->set('translation', $this->translation->getPaymentMethodsTranslations());
    }
}
