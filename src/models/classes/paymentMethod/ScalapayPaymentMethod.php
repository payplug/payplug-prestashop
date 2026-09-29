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
        $option['advanced_options'] = [
            $this->getThresholds(),
        ];

        return $option;
    }

    /**
     * @description Get the back office field allowing the merchant to narrow the Scalapay
     *              amount range. `value` is what the merchant currently gets (their own
     *              limit, or the account's when they never set one) while `default` is
     *              always the account's authorized bound, which is what the widget
     *              validates against client-side - same ceiling as the save-time check.
     *
     * @return array
     */
    public function getThresholds()
    {
        $this->setParameters();

        $account_limits = $this->getScalapayPriceLimit(false);
        $limits = $this->getScalapayPriceLimit();
        $translation = $this->translation[$this->name]['thresholds'];

        $account_min = $this->formatThresholdAmount($account_limits['min']);
        $account_max = $this->formatThresholdAmount($account_limits['max']);

        return [
            'name' => 'thresholds',
            // The amount range illustration carries no Oney branding, so it is shared
            // rather than duplicated for Scalapay.
            'image_url' => $this->img_path . 'oney/' . $this->dependencies->name . '-thresholds.jpg',
            'title' => $translation['title'],
            'descriptions' => [
                'description' => $translation['description'],
                'min_amount' => [
                    'name' => 'scalapay_min_amounts',
                    'value' => $this->formatThresholdAmount($limits['min']),
                    'placeholder' => $this->formatThresholdAmount($limits['min']),
                    'default' => $account_min,
                ],
                'inter' => $translation['inter'],
                'max_amount' => [
                    'name' => 'scalapay_max_amounts',
                    'value' => $this->formatThresholdAmount($limits['max']),
                    'placeholder' => $this->formatThresholdAmount($limits['max']),
                    'default' => $account_max,
                ],
                'error' => [
                    'text' => sprintf($translation['error']['default'], $account_min, $account_max),
                    'maxtext' => sprintf($translation['error']['max'], $account_min, $account_max),
                    'mintext' => sprintf($translation['error']['min'], $account_min, $account_max),
                ],
            ],
            'switch' => false,
        ];
    }

    /**
     * @description Get the Scalapay price limits, in cents.
     *
     *              The account's authorized range comes from the `scalapay` entry of
     *              GET /account (min_amounts/max_amounts), mapped in
     *              API::treatAccountResponse(); there is no hardcoded fallback. The
     *              merchant can only narrow that range from the back office, never widen
     *              it, so an out-of-range custom limit is ignored in favour of the
     *              account's own.
     *
     * @param bool $custom When false, only the account's authorized range is returned
     *
     * @return array Limits in cents, e.g. `['min' => 500, 'max' => 400000]`. A bound the
     *               account does not expose is returned as false.
     */
    public function getScalapayPriceLimit($custom = true)
    {
        $this->setParameters();

        $account_limits = parent::getPriceLimit();
        $limits = [
            'min' => $this->parseAmount(isset($account_limits['min']) ? $account_limits['min'] : ''),
            'max' => $this->parseAmount(isset($account_limits['max']) ? $account_limits['max'] : ''),
        ];

        if (!(bool) $custom) {
            return $limits;
        }

        $custom_min = $this->parseAmount($this->configuration->getValue('scalapay_custom_min_amounts'));
        $custom_max = $this->parseAmount($this->configuration->getValue('scalapay_custom_max_amounts'));

        if (false !== $custom_min && false !== $limits['min'] && $custom_min > $limits['min']) {
            $limits['min'] = $custom_min;
        }
        if (false !== $custom_max && false !== $limits['max'] && $custom_max < $limits['max']) {
            $limits['max'] = $custom_max;
        }

        return $limits;
    }

    /**
     * @description Format a custom Scalapay limit for storage, keeping the currency of
     *              the account's own authorized range so both stay comparable.
     *
     * @param int $amount amount in cents
     *
     * @return string e.g. `EUR:1000`
     */
    public function setCustomScalapayLimit($amount = 0)
    {
        $this->setParameters();

        $account_limits = parent::getPriceLimit();

        return $this->formatAmount(isset($account_limits['min']) ? $account_limits['min'] : '', (int) $amount);
    }

    /**
     * @description Get the price limits gating Scalapay at checkout: the merchant's own
     *              limits narrowed against the account's authorized range, so a cart
     *              outside them hides the payment method.
     *
     * @return array Limits as `ISO:amount` strings, e.g. `['min' => 'EUR:500', 'max' => 'EUR:400000']`
     */
    public function getPriceLimit()
    {
        $account_limits = parent::getPriceLimit();
        $limits = $this->getScalapayPriceLimit();

        return [
            'min' => $this->formatAmount(isset($account_limits['min']) ? $account_limits['min'] : '', $limits['min']),
            'max' => $this->formatAmount(isset($account_limits['max']) ? $account_limits['max'] : '', $limits['max']),
        ];
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
        $amountHelper = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.helper.amount');
        foreach ($products as $product) {
            $unit_price = $amountHelper->convertAmount($product['price_wt']);
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

    /**
     * @description Convert a limit in cents to the amount the back office displays.
     *
     * @param false|int $amount
     *
     * @return float
     */
    private function formatThresholdAmount($amount)
    {
        if (false === $amount) {
            return 0.0;
        }

        return (float) $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.helper.amount')
            ->convertAmount((int) $amount, true);
    }

    /**
     * @description Extract the amount in cents from an `ISO:amount` limit.
     *
     * @param string $limit
     *
     * @return false|int false when the limit carries no amount
     */
    private function parseAmount($limit)
    {
        $parts = explode(':', (string) $limit);

        return isset($parts[1]) && '' !== $parts[1] ? (int) $parts[1] : false;
    }

    /**
     * @description Re-attach the currency prefix of an account limit to a resolved amount,
     *              so the returned limit keeps the shape AmountHelper::validateAmount()
     *              expects.
     *
     * @param string $account_limit
     * @param false|int $amount
     *
     * @return string
     */
    private function formatAmount($account_limit, $amount)
    {
        if (false === $amount) {
            return (string) $account_limit;
        }

        $parts = explode(':', (string) $account_limit);

        return (isset($parts[1]) ? $parts[0] . ':' : '') . $amount;
    }
}
