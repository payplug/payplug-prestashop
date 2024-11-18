<?php

namespace PayPlug\tests\actions\PaymentAction;

/**
 * @group unit
 * @group action
 * @group payment_action
 *
 * @runTestsInSeparateProcesses
 */
class renderPaymentErrorsTest extends BasePaymentAction
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $errors
     */
    public function testWhenGivenErrorsIsntValidArray($errors)
    {
        $this->assertSame(
            '',
            $this->action->renderPaymentErrors($errors)
        );
    }
}
