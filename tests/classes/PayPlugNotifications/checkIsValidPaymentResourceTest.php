<?php

namespace PayPlug\tests\classes\PayPlugNotifications;

use PayPlug\classes\PayPlugNotifications;
use PayPlug\tests\mock\PaymentMock;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group class
 * @group payplug_notifications
 */
class checkIsValidPaymentResourceTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testWhenPaymentHasDefinitiveFailureItIsNotTreatedAsNotPaidYet()
    {
        $notifications = (new \ReflectionClass(PayPlugNotifications::class))->newInstanceWithoutConstructor();

        $logger = \Mockery::mock('Logger');
        $logger->shouldReceive('addLog');
        $notifications->logger = $logger;
        $notifications->is_deferred = false;
        $notifications->is_oney = false;
        $notifications->payment = PaymentMock::getStandard([
            'is_paid' => false,
            'failure' => [
                'code' => 'card_declined',
                'message' => 'The card was declined',
            ],
        ]);

        $check_method = (new \ReflectionClass($notifications))->getMethod('checkIsValidPaymentResource');
        $check_method->setAccessible(true);

        // Before the fix, this condition ignored `failure` and treated a definitively
        // failed payment the same as "not paid yet", calling exitProcess() and dropping
        // the notification instead of letting it reach OrderAction::updateAction() to
        // cancel the order. exitProcess() dereferences $this->dependencies (never set
        // here since the constructor is bypassed), so a regression surfaces as an error
        // instead of a silent pass.
        $check_method->invoke($notifications);

        $this->addToAssertionCount(1);
    }
}
