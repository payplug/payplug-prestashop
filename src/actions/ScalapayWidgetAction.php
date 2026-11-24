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

namespace PayPlug\src\actions;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\classes\DependenciesClass;
use PayPlug\src\utilities\traits\ServiceGetter;

class ScalapayWidgetAction
{
    use ServiceGetter;

    const WIDGET_SCRIPT_URL = 'https://cdn.scalapay.com/widget/v5/js/scalapay-widget.js';

    /**
     * Set to 'integration' by the QA zip build (dev/ci/set_environment.sh), empty in the repository.
     */
    const WIDGET_ENVIRONMENT = '';

    const CURRENCY = 'EUR';

    const DEFAULT_LOCALE = 'en';

    const LOCALES = ['de', 'en', 'es', 'fr', 'it', 'nl', 'pt'];

    const PAGE_CONTROLLERS = [
        'product' => 'product',
        'cart' => 'cart',
        'checkout' => 'order',
    ];

    const THEME_AMOUNT_SELECTORS = [
        'classic' => [
            'product' => ['.current-price-value'],
            'cart' => ['.cart-summary-totals .cart-total .value'],
            'checkout' => ['.cart-summary-totals .cart-total .value'],
        ],
        'hummingbird' => [
            'product' => ['.product__price'],
            'cart' => [],
            'checkout' => [],
        ],
    ];

    public $dependencies;

    private static $product_widget_rendered = false;

    private static $script_rendered = false;

    private static $merchant_currency_supported;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
    }

    /**
     * @description Check whether the widget can be shown on a page
     *
     * @param string $page product, cart or checkout
     * @param float|int|null $amount amount to check against the Scalapay limits, null to skip that check
     *
     * @return bool
     */
    public function isWidgetAvailable($page = '', $amount = null)
    {
        if (!$this->isPageEnabled($page) || !$this->isScalapayOffered()) {
            return false;
        }

        $currency = $this->getCartCurrency();
        $payment_method_class = $this->dependencies->getPlugin()->getPaymentMethodClass();
        $apm_eligibility = $payment_method_class->getApmEligibility();

        return $this->isCurrencySupported($apm_eligibility, $currency)
            && $this->isMerchantCurrency($payment_method_class)
            && (null === $amount || $this->isAmountWithinLimits($apm_eligibility, $amount, $currency));
    }

    /**
     * @description Render the widget after the price of the product the page is about
     *
     * @param array $params displayProductPriceBlock hook parameters
     *
     * @return string
     */
    public function renderProduct($params = [])
    {
        $widget = '';
        if (!self::$product_widget_rendered && $this->isPageProductPriceBlock($params)) {
            $product = $params['product'];
            $price = isset($product['price_amount']) ? (float) $product['price_amount'] : 0.0;
            if ($this->isWidgetAvailable('product', $price * $this->getQuantityWanted($product))) {
                self::$product_widget_rendered = true;
                $widget = $this->renderWidget('product', $price);
            }
        }

        return $widget;
    }

    /**
     * @description Render the widget on the cart page
     *
     * @return string
     */
    public function renderCart()
    {
        $widget = '';
        $cart = $this->dependencies->getPlugin()->getContext()->get()->cart;
        if ('cart' === $this->getController() && is_object($cart)) {
            $total = (float) $cart->getOrderTotal(true);
            if ($this->isWidgetAvailable('cart', $total)) {
                $widget = $this->renderWidget('cart', $total);
            }
        }

        return $widget;
    }

    /**
     * @description Render the widget under the Scalapay payment option, which is only offered to eligible carts
     *
     * @return string
     */
    public function renderCheckout()
    {
        $widget = '';
        if ($this->isWidgetAvailable('checkout')) {
            $cart = $this->dependencies->getPlugin()->getContext()->get()->cart;
            $widget = $this->renderWidget('checkout', is_object($cart) ? (float) $cart->getOrderTotal(true) : null);
        }

        return $widget;
    }

    /**
     * @description Render the widget script on a page where a widget can be shown
     *
     * @return string
     */
    public function renderScriptTag()
    {
        $script = '';
        $page = array_search($this->getController(), self::PAGE_CONTROLLERS, true);
        if (!self::$script_rendered && false !== $page && $this->isWidgetAvailable($page)) {
            self::$script_rendered = true;
            $this->dependencies->getPlugin()->getAssign()->assign([
                'scalapay_widget_script_url' => self::WIDGET_SCRIPT_URL,
            ]);
            $script = (string) $this->dependencies->configClass->fetchTemplate('scalapay/script.tpl');
        }

        return $script;
    }

    /**
     * @description Get the CSS selectors of the price the widget reads on a page, for the shop theme
     *
     * @param string $page product, cart or checkout
     *
     * @return string[] empty for a theme without defaults
     */
    public function getAmountSelectors($page = '')
    {
        $theme = $this->getThemeName();

        return '' !== $theme && isset(self::THEME_AMOUNT_SELECTORS[$theme][$page])
            ? self::THEME_AMOUNT_SELECTORS[$theme][$page]
            : [];
    }

    /**
     * @description Get the widget locale from the shop language
     *
     * @return string
     */
    public function getLocale()
    {
        $context = $this->dependencies->getPlugin()->getContext()->get();
        $iso_code = isset($context->language->iso_code) ? strtolower((string) $context->language->iso_code) : '';

        return in_array($iso_code, self::LOCALES, true) ? $iso_code : self::DEFAULT_LOCALE;
    }

    /**
     * @param mixed $page
     *
     * @return bool whether the widget of the page is switched on, outside the quickview
     */
    private function isPageEnabled($page)
    {
        $plugin = $this->dependencies->getPlugin();

        return is_string($page)
            && isset(self::PAGE_CONTROLLERS[$page])
            && (bool) $plugin->getConfigurationClass()->getValue('scalapay_' . $page . '_widget')
            && 'quickview' !== $plugin->getTools()->tool('getValue', 'action');
    }

    /**
     * @return bool whether Scalapay is offered by the live module with a merchant token
     */
    private function isScalapayOffered()
    {
        if (!$this->dependencies->configClass->isValidFeature('feature_scalapay')
            || !$this->dependencies->configClass->isAllowed()) {
            return false;
        }

        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $payment_methods = json_decode((string) $configuration->getValue('payment_methods'), true);

        return is_array($payment_methods)
            && !empty($payment_methods['scalapay'])
            && !(bool) $configuration->getValue('sandbox_mode')
            && '' !== $this->getMerchantToken();
    }

    /**
     * @return string
     */
    private function getCartCurrency()
    {
        $context = $this->dependencies->getPlugin()->getContext()->get();

        return isset($context->currency->iso_code) ? strtoupper((string) $context->currency->iso_code) : '';
    }

    /**
     * @param \PayPlug\src\models\classes\ApmEligibility $apm_eligibility
     * @param string $currency
     *
     * @return bool
     */
    private function isCurrencySupported($apm_eligibility, $currency)
    {
        if (self::CURRENCY !== $currency) {
            return false;
        }

        $api_limits = $apm_eligibility->getApiLimits('scalapay', $currency);

        return $api_limits['configured'] && $api_limits['currency_supported'];
    }

    /**
     * @param \PayPlug\src\models\classes\paymentMethod\PaymentMethod $payment_method_class
     *
     * @return bool whether the cart currency is one of the merchant currencies, as required to offer the Scalapay option,
     *              computed once per request since it builds every payment method
     */
    private function isMerchantCurrency($payment_method_class)
    {
        if (null === self::$merchant_currency_supported) {
            $scalapay = $payment_method_class->getPaymentMethod('scalapay');
            self::$merchant_currency_supported = is_object($scalapay) && $scalapay->isSupportedCurrency();
        }

        return self::$merchant_currency_supported;
    }

    /**
     * @param \PayPlug\src\models\classes\ApmEligibility $apm_eligibility
     * @param float|int $amount
     * @param string $currency
     *
     * @return bool
     */
    private function isAmountWithinLimits($apm_eligibility, $amount, $currency)
    {
        $cents = $this->toCents($amount, $currency);
        $limits = $apm_eligibility->getEffectiveLimits('scalapay', $currency);

        return $limits['valid']
            && $cents > 0
            && (null === $limits['min'] || $cents >= $limits['min'])
            && (null === $limits['max'] || $cents <= $limits['max']);
    }

    /**
     * @param mixed $params displayProductPriceBlock hook parameters
     *
     * @return bool whether the block is the after-price block of the product the page is about
     */
    private function isPageProductPriceBlock($params)
    {
        $id_product = (int) $this->dependencies->getPlugin()->getTools()->tool('getValue', 'id_product');

        return is_array($params)
            && isset($params['type'], $params['product']['id_product'])
            && 'after_price' === $params['type']
            && 'product' === $this->getController()
            && $id_product > 0
            && $id_product === (int) $params['product']['id_product'];
    }

    /**
     * @param string $page
     * @param float|null $amount price shown on the page, read by the widget when its selectors find none
     *
     * @return string
     */
    private function renderWidget($page, $amount = null)
    {
        $this->dependencies->getPlugin()->getAssign()->assign([
            'scalapay_merchant_token' => $this->getMerchantToken(),
            'scalapay_type' => $page,
            'scalapay_amount_selectors' => json_encode($this->getAmountSelectors($page), JSON_UNESCAPED_SLASHES),
            'scalapay_amount' => null === $amount ? '' : $this->formatAmount($amount),
            'scalapay_environment' => $this->getWidgetEnvironment(),
            'scalapay_locale' => $this->getLocale(),
        ]);

        return (string) $this->dependencies->configClass->fetchTemplate('scalapay/widget.tpl');
    }

    /**
     * @param float $amount
     *
     * @return string the amount in euros with a dot and two decimals, empty when it is not positive
     */
    private function formatAmount($amount)
    {
        $cents = $this->toCents($amount, $this->getCartCurrency());

        return $cents > 0 ? sprintf('%d.%02d', intdiv($cents, 100), $cents % 100) : '';
    }

    /**
     * @param float|int $amount
     * @param string $currency
     *
     * @return int
     */
    private function toCents($amount, $currency)
    {
        return (int) $this->getService('payplug.utilities.helper.amount')->convertAmount($amount, false, $currency);
    }

    /**
     * @return string
     */
    private function getMerchantToken()
    {
        return trim((string) $this->dependencies
            ->getPlugin()
            ->getConfigurationClass()
            ->getValue('scalapay_merchant_token'));
    }

    /**
     * @return string the environment set at build time, else the local SCALAPAY_WIDGET_ENVIRONMENT setting
     */
    private function getWidgetEnvironment()
    {
        return '' !== static::WIDGET_ENVIRONMENT
            ? static::WIDGET_ENVIRONMENT
            : $this->dependencies->getPlugin()->getRoutes()->getScalapayWidgetEnvironment();
    }

    /**
     * @return string
     */
    private function getController()
    {
        return (string) $this->dependencies->getPlugin()->getDispatcher()->getInstance()->getController();
    }

    /**
     * @param array|\ArrayAccess $product
     *
     * @return int
     */
    private function getQuantityWanted($product)
    {
        $quantity = (int) $this->dependencies->getPlugin()->getTools()->tool('getValue', 'quantity_wanted');
        if ($quantity < 1 && isset($product['quantity_wanted'])) {
            $quantity = (int) $product['quantity_wanted'];
        }

        return max(1, $quantity);
    }

    /**
     * @return string the shop theme, or its parent when only the parent has default selectors
     */
    private function getThemeName()
    {
        $shop = $this->dependencies->getPlugin()->getContext()->get()->shop;
        $theme = isset($shop->theme_name) ? (string) $shop->theme_name : '';
        if (isset(self::THEME_AMOUNT_SELECTORS[$theme])) {
            return $theme;
        }

        if (isset($shop->theme) && is_object($shop->theme) && is_callable([$shop->theme, 'get'])) {
            $parent = $shop->theme->get('parent');
            if (is_string($parent) && isset(self::THEME_AMOUNT_SELECTORS[$parent])) {
                return $parent;
            }
        }

        return $theme;
    }
}
