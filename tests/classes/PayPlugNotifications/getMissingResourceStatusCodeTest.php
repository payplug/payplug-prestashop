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
class getMissingResourceStatusCodeTest extends TestCase
{
    private $notifications;

    public function setUp(): void
    {
        $this->notifications = (new \ReflectionClass(PayPlugNotifications::class))->newInstanceWithoutConstructor();
    }

    /**
     * Regression for PRE-3580: on a cart with several payment attempts,
     * PaymentAction::createAction deletes the previous attempt's stored resource before
     * creating the new one. When PayPlug later replays the IPN for that superseded,
     * failed attempt, setPayment() can no longer find a stored resource. That must be
     * acknowledged (200), not answered with a 500 that PayPlug retries forever.
     */
    public function testFailedStandardPaymentWithNoStoredResourceReturns200()
    {
        $this->notifications->resource = PaymentMock::getStandard([
            'failure' => [
                'code' => 'card_declined',
                'message' => 'The card was declined',
            ],
        ]);

        $this->assertSame(200, $this->callGetMissingResourceStatusCode());
    }

    public function testNonFailedPaymentWithNoStoredResourceStillReturns500()
    {
        $this->notifications->resource = PaymentMock::getStandard();

        $this->assertSame(500, $this->callGetMissingResourceStatusCode());
    }

    public function testFailedOneyPaymentStillReturns242()
    {
        $this->notifications->resource = PaymentMock::getOney([
            'failure' => [
                'code' => 'card_declined',
                'message' => 'The card was declined',
            ],
        ]);

        $this->assertSame(242, $this->callGetMissingResourceStatusCode());
    }

    public function testNonFailedOneyPaymentStillReturns242()
    {
        $this->notifications->resource = PaymentMock::getOney();

        $this->assertSame(242, $this->callGetMissingResourceStatusCode());
    }

    private function callGetMissingResourceStatusCode()
    {
        $method = (new \ReflectionClass($this->notifications))->getMethod('getMissingResourceStatusCode');
        $method->setAccessible(true);

        return $method->invoke($this->notifications);
    }
}
