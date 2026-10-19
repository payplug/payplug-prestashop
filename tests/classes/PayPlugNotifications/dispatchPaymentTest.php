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
class dispatchPaymentTest extends TestCase
{
    private $logger;
    private $notifications;
    private $order_action;
    private $order_adapter;

    public function setUp(): void
    {
        $this->notifications = (new \ReflectionClass(PayPlugNotifications::class))->newInstanceWithoutConstructor();

        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive('addLog');
        $this->notifications->logger = $this->logger;
        $this->notifications->cart = (object) ['id' => 42];
        $this->notifications->resource = PaymentMock::getStandard();

        $this->order_action = \Mockery::mock('OrderAction');
        $this->order_adapter = \Mockery::mock('OrderAdapter');

        $order_repository = \Mockery::mock('OrderRepository');
        $order_repository->shouldReceive('getCurrentOrders')->andReturn([['id_order' => 1]]);

        $plugin = \Mockery::mock('Plugin');
        $plugin->shouldReceive([
            'getOrderAction' => $this->order_action,
            'getOrderRepository' => $order_repository,
        ]);

        // exitProcess() ends with header() and exit, so the run is stopped right after
        // its log line, when it reads the queueing feature flag.
        $config_class = \Mockery::mock('ConfigClass');
        $config_class->shouldReceive('isValidFeature')
            ->with('feature_queueing_system')
            ->andThrow(new \RuntimeException('exitProcess reached'));

        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->configClass = $config_class;

        $this->setPrivateProperty('dependencies', $dependencies);
        $this->setPrivateProperty('orderAdapter', $this->order_adapter);
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testUnconvertibleAmountOnOrderCreationAnswers500WithTheReason()
    {
        $this->order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(0);
        $this->order_action->shouldReceive('createAction')
            ->with($this->notifications->resource->id)
            ->andReturn([
                'result' => false,
                'message' => 'The resource amount cannot be converted',
            ]);

        $this->callDispatchPayment();

        $this->logger->shouldHaveReceived('addLog')
            ->with('PayPlugNotifications::exitProcess - An error while order creation: The resource amount cannot be converted', 'error')
            ->once();
        $this->logger->shouldNotHaveReceived('addLog', ['PayPlugNotifications::exitProcess - Order created.', '']);
    }

    public function testUnconvertibleAmountOnOrderUpdateAnswers500WithTheReason()
    {
        $this->order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(7);
        $this->order_action->shouldReceive('updateAction')
            ->with($this->notifications->resource->id)
            ->andReturn([
                'result' => false,
                'message' => 'The resource amount cannot be converted',
            ]);

        $this->callDispatchPayment();

        $this->logger->shouldHaveReceived('addLog')
            ->with('PayPlugNotifications::exitProcess - An error while order update: The resource amount cannot be converted', 'error')
            ->once();
        $this->order_action->shouldNotHaveReceived('createAction');
    }

    private function callDispatchPayment()
    {
        $method = (new \ReflectionClass($this->notifications))->getMethod('dispatchPayment');
        $method->setAccessible(true);

        try {
            $method->invoke($this->notifications);
        } catch (\RuntimeException $exception) {
            $this->assertSame('exitProcess reached', $exception->getMessage());

            return;
        }

        $this->fail('dispatchPayment() did not reach exitProcess()');
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    private function setPrivateProperty($name, $value)
    {
        $property = (new \ReflectionClass($this->notifications))->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($this->notifications, $value);
    }
}
