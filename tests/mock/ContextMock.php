<?php

namespace PayPlug\tests\mock;

class ContextMock
{
    public static function get()
    {
        $context = new \stdClass();
        $context->cart = CartMock::get();
        $context->customer = CustomerMock::get();
        $context->currency = CurrencyMock::get();
        $context->language = LanguageMock::get();
        $context->link = LinkMock::get();
        $context->shop = ShopMock::get();

        return $context;
    }
}
