<?php

namespace PayPlug\tests\models\classes\paymentMethod\InstallmentPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\InstallmentPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseInstallmentPaymentMethod extends BasePaymentMethod
{
    protected function setUp()
    {
        parent::setUp();

        $this->classe = \Mockery::mock(InstallmentPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->classe->set('translation', $this->translation->getPaymentMethodsTranslations());
    }
}
