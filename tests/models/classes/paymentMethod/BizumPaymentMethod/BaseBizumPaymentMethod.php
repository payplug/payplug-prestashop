<?php

namespace PayPlug\tests\models\classes\paymentMethod\BizumPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\BizumPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseBizumPaymentMethod extends BasePaymentMethod
{
    public function setUp(): void
    {
        parent::setUp();

        $this->class = \Mockery::mock(BizumPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->class->set('translation', $this->translation->getPaymentMethodsTranslations());
    }
}
