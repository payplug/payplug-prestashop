<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS.
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
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */

namespace PayPlug\src\models\classes\paymentMethod;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ScalapayPaymentMethod extends PaymentMethod
{
    private $cart_adapter;

    public function __construct($dependencies)
    {
        parent::__construct($dependencies);
        $this->name = 'scalapay';
        $this->order_name = 'scalapay';
        $this->cancellable = false;
    }

    /**
     * @description Get option for given configuration
     *
     * @param array $current_configuration
     *
     * @return array
     */
    public function getOption($current_configuration = [])
    {
        $option = parent::getOption($current_configuration);
        $option['available_test_mode'] = false;

        return $option;
    }

    // todo: add coverage to this method
    public function getPaymentTab()
    {
        $this->setParameters();

        $payment_tab = $this->getDefaultPaymentTab();

        if (empty($payment_tab)) {
            return $payment_tab;
        }

        $payment_tab['payment_method'] = 'scalapay';
        $payment_tab['payment_context'] = $this->getScalapayPaymentContext();

        unset($payment_tab['force_3ds'], $payment_tab['allow_save_card']);

        return $payment_tab;
    }

    /**
     * @description Get Scalapay payment Context
     *
     * @return array
     */
    public function getScalapayPaymentContext()
    {
        $this->setParameters();

        $cart_context = [];
        $cart = $this->cart_adapter->get((int) $this->context->cart->id);
        if (!$this->validate_adapter->validate('isLoadedObject', $cart)) {
            return ['cart' => $cart_context];
        }

        $products = $this->cart_adapter->getProducts($cart);

        foreach ($products as $product) {
            $unit_price = $this->dependencies
                ->getHelpers()['amount']
                ->convertAmount($product['price_wt']);
            $productName = (string) $product['name'] . (isset($product['attributes'])
                    ? ' - ' . $product['attributes']
                    : '');

            $item = [
                'delivery_label' => $this->dependencies
                    ->getPlugin()
                    ->getConfiguration()
                    ->get('PS_SHOP_NAME') . ' store',
                'delivery_type' => 'storepickup',
                'brand' => (isset($product['manufacturer_name']) && $product['manufacturer_name']) ?
                    $this->tools->substr($product['manufacturer_name'], 0, 250) :
                    $this->dependencies
                        ->getPlugin()
                        ->getConfiguration()
                        ->get('PS_SHOP_NAME'),
                'merchant_item_id' => (string) $product['id_product'],
                'name' => $this->tools->substr($productName, 0, 250),
                'expected_delivery_date' => date('Y-m-d', strtotime('+1 week')),
                'total_amount' => $unit_price * $product['cart_quantity'],
                'price' => (int) $unit_price,
                'quantity' => (int) $product['cart_quantity'],
            ];

            $cart_context[] = $item;
        }

        return ['cart' => $cart_context];
    }

    /**
     * @description Set parameters for usage
     */
    protected function setParameters()
    {
        parent::setParameters();

        $this->cart_adapter = $this->cart_adapter ?: $this->dependencies->getPlugin()->getCart();
    }
}
