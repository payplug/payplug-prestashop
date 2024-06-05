<?php

//
//
//namespace PayPlug\tests\actions\CartAction;
//
//
///**
// * @group unit
// * @group action
// * @group cart_action
// *
// * @dontrunTestsInSeparateProcesses
// */
//class renderPaymentCtaTest extends BaseCartAction
//{
//    /**
//     * @description test applepay cart checkout when
//     * it's disabled
//     */
//    public function testWhenApplePayCartIsNotAllowed()
//    {
//
//        $this->configuration
//            ->shouldReceive('getValue')
//            ->with('applepay_cart')
//            ->andReturn(0);
//        $this->configuration
//            ->shouldReceive('getValue')
//            ->with('applepay_checkout')
//            ->andReturn(1);
//        $this->configuration
//            ->shouldReceive('getValue')
//            ->with('sandbox_mode')
//            ->andReturn(1);
//
//        $this->assertFalse($this->action->renderPaymentCTA());
//
//
//    }
//
//    /**
//     * @description est applepay cart is not rendred when
//     * the feature is disabled
//     */
//    public function testWhenApplePayFeatureIsNotAllowed()
//    {
//        $this->configuration
//            ->shouldReceive('getValue')
//            ->with('applepay_cart')
//            ->andReturn(1);
//        $this->configuration
//            ->shouldReceive('getValue')
//            ->with('applepay_checkout')
//            ->andReturn(0);
//        $this->configuration
//            ->shouldReceive('getValue')
//            ->with('sandbox_mode')
//            ->andReturn(1);
//
//        $this->assertFalse($this->action->renderPaymentCTA());
//
//
//    }
//
//    /**
//     * @description  test applepay cart checkout
//     * is denied when sandbox enabled
//     */
//    public function testWhenIsSandboxMode()
//    {
//        $this->configuration
//            ->shouldReceive('getValue')
//            ->with('applepay_cart')
//            ->andReturn(1);
//        $this->configuration
//            ->shouldReceive('getValue')
//            ->with('applepay_checkout')
//            ->andReturn(1);
//        $this->configuration
//            ->shouldReceive('getValue')
//            ->with('sandbox_mode')
//            ->andReturn(1);
//
//        $this->assertFalse($this->action->renderPaymentCTA());
//
//
//    }
//}
