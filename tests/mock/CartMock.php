<?php

namespace PayPlug\tests\mock;

class CartMock
{
    public static function factory()
    {
        return new self();
    }

    public static function get()
    {
        $cart = new \stdClass();
        $cart->id = 1;
        $cart->id_carrier = 1;
        $cart->id_address_delivery = 2;
        $cart->id_address_invoice = 3;
        $cart->id_currency = 1;
        $cart->id_customer = 1;
        $cart->delivery_option = '{1, 2}';
        $cart->date_add = '2021-01-01 00:00:00';
        $cart->date_upd = '2021-01-01 00:00:00';

        return $cart;
    }

    public static function getProducts()
    {
        return [
            [
                'id_product' => 1,
                'cart_quantity' => 1,
                'name' => 'Pull imprimé colibri',
                'manufacturer_name' => 'Studio Design',
                'price_wt' => 34.464,
                'attributes' => 'Size : S',
            ],
            [
                'id_product' => 2,
                'cart_quantity' => 2,
                'name' => 'T-shirt imprimé colibri',
                'manufacturer_name' => 'Studio Design',
                'price_wt' => 22.944,
                'attributes' => 'Size : S- Color : White',
            ],
            [
                'id_product' => 3,
                'cart_quantity' => 3,
                'name' => 'Mug The adventure begins',
                'manufacturer_name' => 'Studio Design',
                'price_wt' => 14.28,
            ],
            [
                'id_product' => 4,
                'cart_quantity' => 4,
                'name' => 'Coussin ours brun',
                'manufacturer_name' => 'Studio Design',
                'price_wt' => 22.68,
                'attributes' => 'Color : White',
            ],
            [
                'id_product' => 5,
                'cart_quantity' => 1,
                'name' => 'Coussin ours brun Coussin ours brun Coussin ours brun Coussin ours brun Coussin ours brun
                Coussin ours brun Coussin ours brun Coussin ours brun Coussin ours brun Coussin ours brun Coussin ours 
                brun Coussin ours brun Coussin ours brun',
                'manufacturer_name' => 'Studio Design Studio DesignStudio DesignStudio Design Studio Design Studio Design
                 Studio Design Studio Design Studio Design Studio Design Studio Design Studio Design Studio Design Studio 
                 Design Studio Design Studio Design Studio Design Studio Design Studio Design',
                'price_wt' => 22.68,
                'attributes' => 'Color : White',
            ],
        ];
    }
}
