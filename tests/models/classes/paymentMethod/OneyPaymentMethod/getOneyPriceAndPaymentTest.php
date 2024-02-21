<?php

//
//
//namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;
//
//use PayPlug\tests\mock\CartMock;
//use PayPlug\tests\mock\ContextMock;
//
///**
// * @group unit
// * @group classes
// * @group payment_method_classes
// *
// * @runTestsInSeparateProcesses
// */
//final class getOneyPriceAndPaymentTest extends BaseOneyPaymentMethod
//{
//    private $cartMock;
//
//    public function setUp()
//    {
//
//        parent::setUp();
//
//        $this->cartMock = CartMock::get();
//        $config_class = \Mockery::mock('ConfigClass');
//        $config_class->shouldReceive([
//                                               'fetchTemplate' => 'oney/popin.tpl',
//                                           ]);
//        $this->dependencies->configClass = $config_class;
////
////        $this->cart->shouldReceive([
////                                       'nbProducts' => 1001,
////                                   ]);
////
////        $config_class = \Mockery::mock('ConfigClass');
////        $config_class->shouldReceive([
////                                         'getIsoCodeByCountryId' => 'fr',
////                                     ]);
////        $this->dependencies->configClass = $config_class;
////
////        $this->country->shouldReceive([
////                                          'getByIso' => 4,
////                                      ]);
////
////        $this->currency->shouldReceive([
////                                           'get' => CurrencyMock::get(),
////                                       ]);
////
////        $this->validators['payment']->shouldReceive([
////                                                        'isValidProductQuantity' => [
////                                                            'result' => true,
////                                                            'message' => '',
////                                                        ],
////                                                    ]);
////
////        $this->repo
////            ->shouldAllowMockingProtectedMethods()
////            ->shouldReceive([
////                                'displayOneyRequiredFields' => 'required_field',
////                                'displayOneyPopin' => 'popin',
////                                'displayOneyPaymentOptions' => 'payment_option',
////                            ])
////        ;
////
//        $this->validate_adapter
//            ->shouldReceive([
//                                'validate' => false,
//                            ]);
//
//    }
//
//    public function validDataProvider()
//    {
//        yield [CartMock::get(), 15000, false];
//        yield [null, 15000, false];
//    }
//
//    /**
//     * @dataProvider validDataProvider
//     *
//     * @param mixed $cart
//     * @param mixed $amount
//     * @param mixed $country
//     */
//    public function testWithValidData($cart, $amount, $country)
//    {
//
//
//        $this->validate_adapter
//            ->shouldReceive([
//                                'validate' => false,
//                            ]);
//        $this->validators['payment']
//            ->shouldReceive([
//                                'isOneyElligible' => [
//                                    'result' => true,
//                                ],
//                                'isValidOneyAmount' => ['result' => true, 'error' => false],
//                            ]);
//
//        $this->classe
//            ->shouldReceive(
//                [ 'getOneyPaymentOptionsList' => ['payment_option_list'],
//                ]
//            );
//        $this->context
//            ->shouldReceive('get')
//            ->andReturn(ContextMock::get())
//        ;
//
////        $this->tools_adapter = \Mockery::mock('ToolsAdapter');
//        $this->tools_adapter
//            ->shouldReceive('tool')
//            ->andReturnUsing(function ($method, $param) {
//                return strtolower($param);
//            });
////
////        $this->plugin
////            ->shouldReceive([
////                                'getTabAdapter' => $this->tab_adapter,
////                                'getLanguage' => $this->languages_adatper,
////                                'getTools' => $this->tools_adapter,
////                            ]);
//
//
//
//        $this->configuration_adapter
//            ->shouldReceive('get')
//            ->with('PS_CURRENCY_DEFAULT')
//            ->andReturn('EUR');
//
//        $this->configuration_adapter
//            ->shouldReceive('get')
//            ->with('PAYPLUG_COMPANY_ISO')
//            ->andReturn('1');
//
//        $this->configuration_adapter
//            ->shouldReceive('get')
//            ->with('PAYPLUG_ONEY_FEES')
//            ->andReturn('1');
//
//
//        $this->currency_adapter->shouldReceive([
//                                                   'get' => 1,
//                                               ]);
//
//
//        $this->assertSame(
//            $this->classe->getOneyPriceAndPaymentOptions($this->cartMock, $amount, $country),
//            [
//                'result' => true,
//                'error' => false,
//                'popin' => 'popin',
//            ]
//        );
//    }
//
//
//}
//
