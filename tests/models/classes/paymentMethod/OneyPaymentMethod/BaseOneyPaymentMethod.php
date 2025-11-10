<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\OneyPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseOneyPaymentMethod extends BasePaymentMethod
{
    public function setUp()
    {
        parent::setUp();

        $this->class = \Mockery::mock(OneyPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->class->set('translation', $this->translation->getPaylaterTranslations());
        $this->class->set('logger', $this->logger);
        $this->configuration->shouldReceive('getValue')
            ->with('multi_account')
            ->andReturn(json_encode([]))
            ->byDefault();
    }
}
