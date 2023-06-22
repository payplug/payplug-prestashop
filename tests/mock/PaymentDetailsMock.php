<?php

namespace PayPlug\tests\mock;

/**
 * Il s'agit de la variable $paymentDetails créée dans payplug.php
 * qui servira à tout le processus de création de paiement dans PaymentRepository
 * Class PaymentDetailsMock.
 */
class PaymentDetailsMock
{
    /**
     * $paymentDetails d'un paiement standard.
     *
     * @return array
     */
    public static function getPaymentDetailsStandard()
    {
        $paymentDetails = self::getPaymentDetailsRaw();
        $paymentDetails['paymentMethod'] = 'standard';
        $paymentDetails['paymentTab'] = PaymentTabMock::getStandard();
        $paymentDetails['paymentId'] = 'pay_6PSW2vfJxvT0Ji9c8iPZsw';
        $paymentDetails['isPaid'] = false;

        return $paymentDetails;
    }

    /**
     * $paymentDetails d'un paiement fractionné 3x.
     *
     * @return array
     */
    public static function getPaymentDetailsInstallment()
    {
        $paymentDetails = self::getPaymentDetailsRaw();
        $paymentDetails['paymentMethod'] = 'installment';
        $paymentDetails['paymentTab'] = PaymentTabMock::getInstallment();
        $paymentDetails['paymentId'] = 'inst_13TPOO67A5prqs9zZXW3qW';
        $paymentDetails['isPaid'] = null;

        return $paymentDetails;
    }

    public static function getPaymentDetailsRaw()
    {
        return [
            'paymentMethod' => 'raw',
            'paymentTab' => ['raw'],
            'paymentId' => 'raw',
            'paymentReturnUrl' => 'http =>//localhost/prestashop_1.7.6.9/fr/module/payplug/validation?ps=1&cartid=21',
            'paymentUrl' => 'https =>//secure-qa.payplug.com/pay/6PSW2vfJxvT0Ji9c8iPZsw',
            'paymentDate' => null,
            'authorizedAt' => null,
            'isPaid' => 'raw',
            'isDeferred' => false,
            'isEmbedded' => '0',
            'isMobileDevice' => false,
            (object) ['cart' => [
                'id' => 21,
                'id_shop_group' => '1',
                'id_shop' => '1',
                'id_address_delivery' => '8',
                'id_address_invoice' => '8',
                'id_currency' => '1',
                'id_customer' => '4',
                'id_guest' => '9',
                'id_lang' => '1',
                'recyclable' => '0',
                'gift' => '0',
                'gift_message' => '',
                'mobile_theme' => '0',
                'date_add' => null,
                'secure_key' => '9e570662791c33af33bbd3c8ebdc5a34',
                'id_carrier' => '3',
                'date_upd' => null,
                'checkedTos' => false,
                'pictures' => null,
                'textFields' => null,
                'delivery_option' => '{"8":"3,"}',
                'allow_seperated_package' => '0',
                '_products' => [
                    0 => [
                        'id_product_attribute' => '13',
                        'id_product' => '3',
                        'cart_quantity' => '8',
                        'id_shop' => '1',
                        'id_customization' => null,
                        'name' => 'Affiche encadrée The best is yet to come',
                        'is_virtual' => '0',
                        'description_short' => '<p><span style="font-size:10pt;font-style:normal;">Affiche imprimée sur papier rigide, finition mate et surface lisse.</span></p>',
                        'available_now' => '',
                        'available_later' => '',
                        'id_category_default' => '9',
                        'id_supplier' => '1',
                        'id_manufacturer' => '2',
                        'manufacturer_name' => 'Graphic Corner',
                        'on_sale' => '0',
                        'ecotax' => '0.000000',
                        'additional_shipping_cost' => '0.00',
                        'available_for_order' => '1',
                        'show_price' => '1',
                        'price' => 29.0,
                        'active' => '1',
                        'unity' => '',
                        'unit_price_ratio' => '0.000000',
                        'quantity_available' => '885',
                        'width' => '0.000000',
                        'height' => '0.000000',
                        'depth' => '0.000000',
                        'out_of_stock' => '2',
                        'weight' => 0.0,
                        'available_date' => '0000-00-00',
                        'date_add' => '2021-02-13 09 =>33 =>03',
                        'date_upd' => '2021-02-13 09 =>33 =>03',
                        'quantity' => 8,
                        'link_rewrite' => 'affiche-encadree-the-best-is-yet-to-come',
                        'category' => 'art',
                        'unique_id' => '0000000003000000001380',
                        'id_address_delivery' => '8',
                        'advanced_stock_management' => '0',
                        'supplier_reference' => null,
                        'customization_quantity' => null,
                        'price_attribute' => '0.000000',
                        'ecotax_attr' => '0.000000',
                        'reference' => 'demo_6',
                        'weight_attribute' => 0.0,
                        'ean13' => '',
                        'isbn' => '',
                        'upc' => '',
                        'minimal_quantity' => '1',
                        'wholesale_price' => '0.000000',
                        'id_image' => '3-3',
                        'legend' => 'Affiche encadrée The best is yet to come',
                        'reduction_type' => 0,
                        'is_gift' => false,
                        'reduction' => 0.0,
                        'reduction_without_tax' => 0.0,
                        'price_without_reduction' => 34.8,
                        'specific_prices' => [],
                        'stock_quantity' => 885,
                        'price_without_reduction_without_tax' => 29.0,
                        'price_with_reduction' => 34.8,
                        'price_with_reduction_without_tax' => 29.0,
                        'total' => 232.0,
                        'total_wt' => 278.4,
                        'price_wt' => 34.8,
                        'reduction_applies' => false,
                        'quantity_discount_applies' => false,
                        'allow_oosp' => 0,
                        'features' => [
                            0 => [
                                'id_feature' => '1',
                                'id_product' => '3',
                                'id_feature_value' => '6', ],
                        ],
                        'attributes' => 'Dimension  => 40x60cm',
                        'attributes_small' => '40x60cm',
                        'rate' => 20.0,
                        'tax_name' => 'TVA FR 20%', ], ],
                '_taxCalculationMethod' => '0',
                'webserviceParameters' => [
                    'fields' => [
                        'id_address_delivery' => [
                            'xlink_resource' => 'addresses', ],
                        'id_address_invoice' => [
                            'xlink_resource' => 'addresses', ],
                        'id_currency' => [
                            'xlink_resource' => 'currencies', ],
                        'id_customer' => [
                            'xlink_resource' => 'customers', ],
                        'id_guest' => [
                            'xlink_resource' => 'guests', ],
                        'id_lang' => [
                            'xlink_resource' => 'languages', ],
                    ],
                    'associations' => [
                        'cart_rows' => [
                            'resource' => 'cart_row',
                            'virtual_entity' => true,
                            'fields' => [
                                'id_product' => [
                                    'required' => true,
                                    'xlink_resource' => 'products', ],
                                'id_product_attribute' => [
                                    'required' => true,
                                    'xlink_resource' => 'combinations', ],
                                'id_address_delivery' => [
                                    'required' => true,
                                    'xlink_resource' => 'addresses', ],
                                'id_customization' => [
                                    'required' => false,
                                    'xlink_resource' => 'customizations', ],
                                'quantity' => [
                                    'required' => true, ],
                            ],
                        ],
                    ],
                ],
                (object) ['configuration' => ['shop' => null,
                    'parameters' => null, ],
                ],
                (object) ['addressFactory' => []],
                'shouldSplitGiftProductsQuantity' => false,
                'shouldExcludeGiftsDiscount' => false,
                'id_shop_list' => [],
                'get_shop_from_context' => true,
                'table' => 'cart',
                'identifier' => 'id_cart',
                'fieldsRequired' => [
                    0 => 'id_currency',
                    1 => 'id_lang', ],
                'fieldsSize' => [
                    'secure_key' => 32, ],
                'fieldsValidate' => [
                    'id_shop_group' => 'isUnsignedId',
                    'id_shop' => 'isUnsignedId',
                    'id_address_delivery' => 'isUnsignedId',
                    'id_address_invoice' => 'isUnsignedId',
                    'id_carrier' => 'isUnsignedId',
                    'id_currency' => 'isUnsignedId',
                    'id_customer' => 'isUnsignedId',
                    'id_guest' => 'isUnsignedId',
                    'id_lang' => 'isUnsignedId',
                    'recyclable' => 'isBool',
                    'gift' => 'isBool',
                    'gift_message' => 'isMessage',
                    'mobile_theme' => 'isBool',
                    'allow_seperated_package' => 'isBool',
                    'date_add' => 'isDate',
                    'date_upd' => 'isDate', ],
                'fieldsRequiredLang' => [],
                'fieldsSizeLang' => [],
                'fieldsValidateLang' => [],
                'tables' => [],
                'image_dir' => null,
                'image_format' => 'jpg',
                'translator' => null,
                'def' => [
                    'table' => 'cart',
                    'primary' => 'id_cart',
                    'fields' => [
                        'id_shop_group' => [
                            'type' => 1,
                            'validate' => 'isUnsignedId',
                        ],
                        'id_shop' => [
                            'type' => 1,
                            'validate' => 'isUnsignedId',
                        ],
                        'id_address_delivery' => [
                            'type' => 1,
                            'validate' => 'isUnsignedId',
                        ],
                        'id_address_invoice' => [
                            'type' => 1,
                            'validate' => 'isUnsignedId',
                        ],
                        'id_carrier' => [
                            'type' => 1,
                            'validate' => 'isUnsignedId',
                        ],
                        'id_currency' => [
                            'type' => 1,
                            'validate' => 'isUnsignedId',
                            'required' => true,
                        ],
                        'id_customer' => [
                            'type' => 1,
                            'validate' => 'isUnsignedId',
                        ],
                        'id_guest' => [
                            'type' => 1,
                            'validate' => 'isUnsignedId',
                        ],
                        'id_lang' => [
                            'type' => 1,
                            'validate' => 'isUnsignedId',
                            'required' => true,
                        ],
                        'recyclable' => [
                            'type' => 2,
                            'validate' => 'isBool',
                        ],
                        'gift' => [
                            'type' => 2,
                            'validate' => 'isBool',
                        ],
                        'gift_message' => [
                            'type' => 3,
                            'validate' => 'isMessage',
                        ],
                        'mobile_theme' => [
                            'type' => 2,
                            'validate' => 'isBool',
                        ],
                        'delivery_option' => [
                            'type' => 3,
                        ],
                        'secure_key' => [
                            'type' => 3,
                            'size' => 32,
                        ],
                        'allow_seperated_package' => [
                            'type' => 2,
                            'validate' => 'isBool',
                        ],
                        'date_add' => [
                            'type' => 5,
                            'validate' => 'isDate',
                        ],
                        'date_upd' => [
                            'type' => 5,
                            'validate' => 'isDate',
                        ],
                    ],
                    'classname' => 'Cart',
                ],
                'update_fields' => null,
                'force_id' => false,
            ],
            ],
            'cartId' => 21,
            'cartHash' => null,
            'forceHash' => true,
        ];
    }
}
