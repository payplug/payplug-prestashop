<?php

namespace PayPlug\tests\models\classes\paymentMethod\InstallmentPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\InstallmentPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseInstallmentPaymentMethod extends BasePaymentMethod
{
    public function setUp()
    {
        parent::setUp();

        $this->helpers['amount']->shouldReceive('validateAmount')
            ->andReturn([
                'result' => true,
                'message' => '',
            ]);

        $this->class = \Mockery::mock(InstallmentPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->class->set('translation', $this->translation->getPaymentMethodsTranslations());
    }
}
