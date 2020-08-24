<?php
/**
 * 2013 - 2020 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author PayPlug SAS
 * @copyright 2013 - 2020 PayPlug SAS
 * @license https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PayPlug SAS
 */

class PayPlugPaymentOney extends PayplugPayment
{
    /** @var array Addition payment tab definition */
    private $additionnal_definition = array(
        'payment_context' => array(
            'type' => 'array',
            'fields' => array(
                'cart' => array(
                    'type' => 'iterable',
                    'fields' => array(
                        'brand' => array(
                            'type' => 'string',
                            'validate' => 'isCleanHtml',
                            'required' => true,
                            'size' => 100
                        ),
                        'expected_delivery_date' => array('type' => 'date', 'validate' => 'isDate', 'required' => true),
                        'delivery_label' => array(
                            'type' => 'string',
                            'validate' => 'isCleanHtml',
                            'required' => true,
                            'size' => 100
                        ),
                        'delivery_type' => array(
                            'type' => 'string',
                            'validate' => 'isName',
                            'allowed' => array('storepickup', 'networkpickup', 'travelpickup', 'carrier', 'edelivery'),
                            'required' => true
                        ),
                        'merchant_item_id' => array(
                            'type' => 'int',
                            'validate' => 'isInt',
                            'required' => true,
                            'size' => 256
                        ),
                        'name' => array(
                            'type' => 'string',
                            'validate' => 'isCleanHtml',
                            'required' => true,
                            'size' => 256
                        ),
                        'price' => array('type' => 'int', 'validate' => 'isInt', 'required' => true),
                        'quantity' => array('type' => 'int', 'validate' => 'isInt', 'required' => true),
                        'total_amount' => array('type' => 'int', 'validate' => 'isInt', 'required' => true),
                    ),
                ),
            ),
            'required' => true
        ),
        'authorized_amount' => array('type' => 'int', 'validate' => 'isInt', 'required' => true),
        'payment_method' => array('type' => 'string', 'validate' => 'isCleanHtml', 'required' => true),
        'force_3ds' => array('type' => 'bool', 'validate' => 'isBool', 'required' => false, 'default' => false),
    );

    /** @var string */
    protected $oney_type;

    /** @var array */
    protected $oney_form;

    /**
     * Constructor
     *
     * @param string $id_card
     * @return PayplugPayment
     */
    public function __construct($id_card = null, $options = array(), Context $context = null)
    {
        parent::__construct($id_card, $options, $context);

        $this->definition_tab = array_merge($this->definition_tab, $this->additionnal_definition);

        $this->type = 'oney';
        $this->oney_type = $options['oney_type'];
        $this->oney_form = $options['oney_form'];

        $this->generatePaymentTab();
        $this->validatePaymentTab();

        return $this;
    }

    /**
     * Generate the tab to create the installment in Payplug API
     */
    public function generatePaymentTab()
    {
        parent::generatePaymentTab();

        $this->payment_tab['authorized_amount'] = $this->getCartAmount($this->payment_tab['currency']);
        $this->payment_tab['payment_context'] = $this->generateCartTab();
        $this->payment_tab['payment_method'] = $this->getPaymentMethodFromType();
        $this->payment_tab['hosted_payment']['return_url'] = PayplugBackward::getModuleLink('payplug', 'validation',
            array('ps' => 1, 'cartid' => (int)$this->cart->id), true);
        $this->payment_tab['force_3ds'] = false;
        $this->payment_tab['auto_capture'] = true;

        $this->hydrateFromAdditionalFields();
        $this->checkAddressFields();
    }

    /**
     * Generate payment context tab
     * @return array
     */
    public function generateCartTab()
    {
        $items = array();
        $summary = $this->cart->getSummaryDetails();

        // treat products from cart
        foreach ($summary['products'] as $product) {
            $product_key = (int)$product['id_product'] . '_' . (int)$product['id_product_attribute'];
            $item = $this->formatCartItem($product);
            $items[$product_key] = $item;
        }

        // treat gift products from cart
        foreach ($summary['gift_products'] as $product) {
            $product_key = (int)$product['id_product'] . '_' . (int)$product['id_product_attribute'];

            if (array_key_exists($product_key, $items)) {
                $items[$product_key]['quantity']++;
            } else {
                $item = $this->formatCartItem($product);
                $items[$product_key] = $item;
            }
        }

        $delivery = $this->getDeliveryData($summary['carrier']);

        foreach ($items as &$item) {
            $item = array_merge($item, $delivery);
        }

        sort($items);

        return ['cart' => $items];
    }

    /**
     * Get the payment method From Oney type
     * @return string oney_3x|oney_4x
     */
    public function getPaymentMethodFromType()
    {
        $oney_type = explode('_', $this->oney_type);
        return 'oney_x' . str_replace('x', '', $oney_type[0]) . '_with_fees';
    }

    /**
     * Format Cart item for payment tab
     * @param $product
     * @return array
     */
    private function formatCartItem($product)
    {
        $unit_price = $this->module->convertAmount($product['price_wt']);
        $item = array(
            'merchant_item_id' => $product['id_product'],
            'name' => (string)$product['name'] . (isset($product['attributes']) ? ' - ' . $product['attributes'] : ''),
            'price' => (int)$unit_price,
            'quantity' => (int)$product['cart_quantity'],
            'total_amount' => (string)$unit_price * $product['cart_quantity'],
        );

        // Set brand data
        $manufacturer = new Manufacturer($product['id_manufacturer']);
        if (Validate::isLoadedObject($manufacturer)) {
            $name = $manufacturer->name;
        } else {
            $name = $this->module->getConfiguration('PS_SHOP_NAME');
        }
        $item['brand'] = $name;

        return $item;
    }

    /**
     * Get delivery data from current Carrier
     *
     * @param $carrier Carrier
     * @return array
     */
    private function getDeliveryData($carrier)
    {
        $delivery_data = array();

        if (!Validate::isLoadedObject($this->cart)) {
            // todo: log error
            return $delivery_data;
        }

        if($this->cart->isVirtualCart()) {
            $delivery_data['delivery_label'] = $this->module->getConfiguration('PS_SHOP_NAME');
            $delivery_data['expected_delivery_date'] = date('Y-m-d');
            $delivery_data['delivery_type'] = 'edelivery';
        } else {
            if (!Validate::isLoadedObject($carrier)) {
                // todo: log error
                return $delivery_data;
            }

            $payplug_carrier = new PayPlugCarrier();
            $payplug_carrier = $payplug_carrier->getByIdCarrier($carrier->id);

            if (!Validate::isLoadedObject($payplug_carrier)) {
                // todo: log error
                $delay = PayPlugCarrier::CARRIER_DEFAULT_DELAY;
                $delivery_type = PayPlugCarrier::CARRIER_DEFAULT_DELIVERY_TYPE;
                $delivery_data['delivery_label'] = $this->module->getConfiguration('PS_SHOP_NAME');
                $delivery_data['expected_delivery_date'] = date(
                    'Y-m-d',
                    strtotime(date('Y-m-d') . ' + ' . $delay . ' days')
                );
                $delivery_data['delivery_type'] = $delivery_type;
                return $delivery_data;
            }

            $delivery_data['delivery_label'] = $carrier->name;
            $delivery_data['expected_delivery_date'] = date(
                'Y-m-d',
                strtotime(date('Y-m-d') . ' + ' . $payplug_carrier->delay . ' days')
            );
            $delivery_data['delivery_type'] = $payplug_carrier->delivery_type;
        }

        return $delivery_data;
    }

    /**
     * Get additionnal required field for Oney payment
     */
    private function hydrateFromAdditionalFields()
    {
        if ($this->oney_form) {
            foreach($this->oney_form as $k => $field) {
                $keys = explode('-',$k);
                $type = $keys[0];
                $field_name = $keys[1];

                if (strpos($field_name, 'phone') != false) {
                    switch($type) {
                        case 'billing' :
                            $id_country = Country::getByIso($this->payment_tab['billing']['country']);
                            $country = new Country($id_country);
                            $field = $this->formatPhoneNumber($field, $country);
                            break;
                        case 'same' :
                        case 'shipping' :
                        default:
                            $id_country = Country::getByIso($this->payment_tab['shipping']['country']);
                            $country = new Country($id_country);
                            $field = $this->formatPhoneNumber($field, $country);
                            break;
                    }
                }

                if ($field_name == 'email') {
                    $this->payment_tab['billing']['email'] = $field;
                    $this->payment_tab['shipping']['email'] = $field;
                } elseif ($type == 'same') {
                    $this->payment_tab['billing'][$field_name] = $field;
                    $this->payment_tab['shipping'][$field_name] = $field;
                } else {
                    $this->payment_tab[$type][$field_name] = $field;
                }
            }
        }
    }

    private function checkAddressFields(){
        // check company name
        $this->payment_tab['billing']['company_name'] = $this->payment_tab['billing']['company_name'] ?
            $this->payment_tab['billing']['company_name'] :
            $this->payment_tab['billing']['first_name'] . ' ' . $this->payment_tab['billing']['last_name'];
        $this->payment_tab['shipping']['company_name'] = $this->payment_tab['shipping']['company_name'] ?
            $this->payment_tab['shipping']['company_name'] :
            $this->payment_tab['shipping']['first_name'] . ' ' . $this->payment_tab['shipping']['last_name'];
    }
}
