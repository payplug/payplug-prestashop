<?php

namespace PayPlug\tests\actions\UnifiedOrderDetailAction;

use PayPlug\src\actions\UnifiedOrderDetailAction;
use PayPlug\src\utilities\helpers\AmountHelper;
use PayplugUnifiedCore\DataValues\OperationData;
use PayplugUnifiedCore\DataValues\PaymentOutcome;
use PHPUnit\Framework\TestCase;

abstract class BaseUnifiedOrderDetailAction extends TestCase
{
    protected const OPERATION_ID = '359fe258-8264-4a90-9a40-d16e1736058d';
    protected const PAYMENT_ID = 'd4293432-a0ac-4d4f-ae5b-8b12253ecbec';
    protected const SNAPSHOT = [
        'brand' => 'VISA',
        'last4' => '0001',
        'exp_month' => '12',
        'exp_year' => '2029',
        'tds' => 'yes',
    ];

    /** @var UnifiedOrderDetailAction */
    protected $action;
    protected $configuration_class;
    protected $factory;
    protected $logger;
    protected $operation_repository;
    protected $order_action;
    protected $token_cache;

    public function setUp(): void
    {
        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';
        $plugin = \Mockery::mock('Plugin');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $translation_class = \Mockery::mock('TranslationClass');
        $price_adapter = \Mockery::mock('PriceAdapter');
        $this->configuration_class = \Mockery::mock('ConfigurationClass');
        $this->factory = \Mockery::mock('Factory');
        $this->logger = \Mockery::mock('Logger');
        $this->operation_repository = \Mockery::mock('UnifiedOperationRepository');
        $this->order_action = \Mockery::mock('UnifiedOrderAction');
        $this->token_cache = \Mockery::mock('TokenCache');

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);
        $plugin->shouldReceive('getTranslationClass')->andReturn($translation_class);
        $plugin->shouldReceive('getConfigurationClass')->andReturn($this->configuration_class);
        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')->with('payplug.unified.payment_service_factory')->andReturn($this->factory);
        $module->shouldReceive('getService')->with('payplug.utilities.helper.amount')->andReturn(new AmountHelper(\Mockery::mock('Dependencies')));
        $module->shouldReceive('getService')->with('payplug.application.adapter.price')->andReturn($price_adapter);
        $module->shouldReceive('getService')->with('payplug.action.unified_order')->andReturn($this->order_action);
        $this->factory->shouldReceive('createPaymentRepository')->andReturn($this->operation_repository);
        $this->factory->shouldReceive('createLogger')->andReturn($this->logger);
        $this->factory->shouldReceive('createTokenCache')->andReturn($this->token_cache);
        $price_adapter->shouldReceive('formatPrice')->andReturnUsing(function ($price, $iso_code) {
            return $iso_code . ' ' . number_format($price, 2);
        });
        $translation_class->shouldReceive('getOrderTranslations')->andReturn([
            'module_name' => ['default' => 'Carte Bancaire by Payplug'],
            'detail' => [
                'mode' => ['live' => 'Live', 'test' => 'Test'],
                'status' => [
                    'paid' => 'Paid',
                    'partially_refunded' => 'Partially refunded',
                    'refunded' => 'Refunded',
                ],
                'yes' => 'Yes',
                'no' => 'No',
                'card' => 'Card',
            ],
        ]);

        $this->configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn('0')->byDefault();
        $this->operation_repository->shouldReceive('getPaymentIdByOperationId')->with(self::OPERATION_ID)->andReturn(self::PAYMENT_ID)->byDefault();
        $this->operation_repository->shouldReceive('getPaymentDetails')->with(self::OPERATION_ID)->andReturn(self::SNAPSHOT)->byDefault();
        $this->operation_repository->shouldReceive('getDateAddByOperationId')->with(self::OPERATION_ID)->andReturn('2026-11-10 14:05:00')->byDefault();
        $this->order_action->shouldReceive('syncOrderPayment')->never();
        $this->token_cache->shouldReceive('get')->with('uhf_details_fetch_failed:' . self::OPERATION_ID)->andReturn(null)->byDefault();
        $this->token_cache->shouldReceive('set')->never()->byDefault();

        $this->action = (new \ReflectionClass(UnifiedOrderDetailAction::class))->newInstanceWithoutConstructor();
        $this->action->dependencies = $dependencies;
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }

    /**
     * @param int $amount in minor units
     *
     * @return OperationData
     */
    protected function paidOperation($amount = 2900)
    {
        return new OperationData(self::OPERATION_ID, '0000', PaymentOutcome::PAID, $amount, '42');
    }

    /**
     * @param string $iso_code
     *
     * @return object
     */
    protected function currency($iso_code = 'USD')
    {
        return (object) ['iso_code' => $iso_code];
    }

    /**
     * @param string|null $date_add
     *
     * @return object
     */
    protected function order($date_add = '2026-11-09 10:00:00')
    {
        return (object) ['id' => 42, 'id_cart' => 6, 'date_add' => $date_add];
    }

    /**
     * @param array<string, mixed>|string $body decoded operation, or the raw body
     *
     * @return object the payment service mock
     */
    protected function mockGetOperation($body)
    {
        $payment_service = \Mockery::mock('UnifiedApiPaymentService');
        $this->factory->shouldReceive('create')->andReturn($payment_service);
        $payment_service->shouldReceive('getOperation')
            ->once()
            ->with(self::OPERATION_ID)
            ->andReturn(['status' => 200, 'body' => is_string($body) ? $body : json_encode($body)]);

        return $payment_service;
    }
}
