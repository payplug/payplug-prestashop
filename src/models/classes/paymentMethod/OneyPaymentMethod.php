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

class OneyPaymentMethod extends PaymentMethod
{
    private $oney_allowed_iso_codes = ['FR', 'IT', 'ES', 'NL'];

    public function __construct($dependencies)
    {
        parent::__construct($dependencies);
        $this->name = 'oney';
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
        $this->setParameters();

        if (!is_array($current_configuration)) {
            $this->logger->addLog('OneyPaymentMethod::getOption: Invalid parameter given, $current_configuration must be an array.');

            return [];
        }

        $amounts = json_decode($this->configuration->getDefault('amounts'), true);
        $payment_methods = json_decode($this->configuration->getDefault('payment_methods'), true);
        $default_configuration = [
            'oney' => (bool) $payment_methods['oney'],
            'oney_min_amounts' => isset($amounts['oney_x3_with_fees']) ? $amounts['oney_x3_with_fees']['min'] : '',
            'oney_max_amounts' => isset($amounts['oney_x3_with_fees']) ? $amounts['oney_x3_with_fees']['max'] : '',
            'oney_custom_min_amounts' => isset($amounts['oney_x3_with_fees']) ? $amounts['oney_x3_with_fees']['min'] : '',
            'oney_custom_max_amounts' => isset($amounts['oney_x3_with_fees']) ? $amounts['oney_x3_with_fees']['max'] : '',
            'oney_product_animation' => $this->configuration->getDefault('oney_product_animation'),
            'oney_cart_animation' => $this->configuration->getDefault('oney_cart_animation'),
            'oney_schedule' => $this->configuration->getDefault('oney_schedule'),
            'oney_fees' => $this->configuration->getDefault('oney_fees'),
        ];
        foreach ($default_configuration as $k => $v) {
            if (!isset($current_configuration[$k])) {
                $current_configuration[$k] = $v;
            }
        }

        $advanced_options = [];
        $thresholds = $this->getThresholds($current_configuration);
        if ($thresholds) {
            $advanced_options[] = $thresholds;
        }
        $schedules = $this->getSchedule((bool) $current_configuration['oney_schedule']);
        if ($schedules) {
            $advanced_options[] = $schedules;
        }

        $can_use_cta = !in_array(
            $this->configuration->getValue('oney_allowed_countries'),
            ['ES', 'BE']
        );
        if ($can_use_cta) {
            $product = $this->getProductCallToAction((bool) $current_configuration['oney_product_animation']);
            if ($product) {
                $advanced_options[] = $product;
            }
            $cart = $this->getCartCallToAction((bool) $current_configuration['oney_cart_animation']);
            if ($cart) {
                $advanced_options[] = $cart;
            }
        }

        return [
            'name' => 'paymentMethodsBlock',
            'title' => $this->translation['title'],
            'descriptions' => [
                'live' => [
                    'description' => $this->translation['description'],
                ],
                'sandbox' => [
                    'description' => $this->translation['description'],
                ],
            ],
            'options' => [
                'name' => 'oney',
                'title' => $this->translation['options']['title'],
                'image' => 'assets/images/lg-oney.png',
                'checked' => $current_configuration['oney'],
                'descriptions' => [
                    'live' => [
                        'description' => $this->translation['options']['description'],
                        'link_know_more' => [
                            'text' => $this->translation['link'],
                            'url' => $this->external_url['oney'],
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => $this->translation['options']['description'],
                        'link_know_more' => [
                            'text' => $this->translation['link'],
                            'url' => $this->external_url['oney'],
                            'target' => '_blank',
                        ],
                    ],
                    'advanced' => [
                        'description' => $this->translation['advanced'],
                    ],
                ],
                'options' => [
                    [
                        'name' => 'payplug_oney',
                        'className' => '_paylaterLabel',
                        'label' => $this->translation['options']['with_fees']['label'],
                        'subText' => $this->translation['options']['with_fees']['subtext'],
                        'value' => 1,
                        'checked' => $this->configuration->getValue('oney_fees'),
                    ],
                    [
                        'name' => 'payplug_oney',
                        'className' => '_paylaterLabel',
                        'label' => $this->translation['options']['without_fees']['label'],
                        'subText' => $this->translation['options']['without_fees']['subtext'],
                        'value' => 0,
                        'checked' => !$this->configuration->getValue('oney_fees'),
                    ],
                ],
                'advanced_options' => $advanced_options,
            ],
        ];
    }

    /**
     * @description Get payment option
     *
     * @param array $payment_options
     *
     * @return array
     */
    protected function getPaymentOption($payment_options = [])
    {
        if (!is_array($payment_options)) {
            return [];
        }

        $this->setParameters();

        $use_taxes = (bool) $this->dependencies
            ->getPlugin()
            ->getConfiguration()
            ->get('PS_TAX');
        $cart_amount = $this->context->cart->getOrderTotal($use_taxes);

        $is_elligible = $this->dependencies
            ->getPlugin()
            ->getOney()
            ->isOneyElligible($this->context->cart, $cart_amount, true);
        $error = $is_elligible['result'] ? false : $is_elligible['error_type'];
        $err_label = $this->getErrorLabel($error);

        $optimized = $this->configuration->getValue('oney_optimized') && !$error;

        $use_fees = (bool) $this->configuration->getValue('oney_fees');
        $delivery_address = $this->dependencies
            ->getPlugin()
            ->getAddress()
            ->get($this->context->cart->id_address_delivery);
        $iso = $this->dependencies
            ->getPlugin()
            ->getTools()
            ->tool('strtoupper', $this->context->language->iso_code);

        if (!in_array($iso, $this->oney_allowed_iso_codes)) {
            $iso = $this->configuration->getValue('company_iso');
        }

        $available_oney_payments = $this->dependencies
            ->getPlugin()
            ->getOney()
            ->getOperations();

        foreach ($available_oney_payments as $oney_payment) {
            $with_fees = false !== (bool) strpos($oney_payment, 'with_fees');
            if (($use_fees && !$with_fees) || (!$use_fees && $with_fees)) {
                continue;
            }

            $this->name = 'oney_' . $oney_payment;
            $payment_options = parent::getPaymentOption($payment_options);

            if (!isset($payment_options[$this->name])) {
                continue;
            }

            $type = explode('_', $oney_payment);
            $split = (int) str_replace('x', '', $type[0]);

            $oneyLogo = $oney_payment . (!$use_fees ? '_side_' . $iso : '') . ($error ? '_alt' : '') . '.svg';
            $text = $use_fees
                ? $this->dependencies->l('payplug.getPaymentOptions.payWithOney', 'oneypaymentmethod')
                : $this->dependencies->l('payplug.getPaymentOptions.payWithOneyWithout', 'oneypaymentmethod');

            $oneyLabel = $error ? $err_label : sprintf($text, $split);

            if ($optimized) {
                $adapter = $this->dependencies->loadAdapterPresta();
                if ($adapter && method_exists($adapter, 'getPaymentOption')) {
                    $oneyData = $adapter->getPaymentOption();
                    $oneyLogo = $oneyData['oneyLogo'];
                    $oneyLabel = $oneyData['oneyCallToActionText'];
                }
            }

            $payment_options[$this->name]['name'] = 'oney';
            $payment_options[$this->name]['inputs']['method']['value'] = 'oney';
            $payment_options[$this->name]['is_optimized'] = $optimized;
            $payment_options[$this->name]['type'] = $oney_payment;
            $payment_options[$this->name]['amount'] = $cart_amount;
            $payment_options[$this->name]['iso_code'] = $this->dependencies
                ->configClass
                ->getIsoCodeByCountryId((int) $delivery_address->id_country);
            $payment_options[$this->name]['inputs']['oney_type'] = [
                'name' => $this->dependencies->name . 'Oney_type',
                'type' => 'hidden',
                'value' => $oney_payment,
            ];
            $payment_options[$this->name]['extra_classes'] = sprintf('oney%sx', $split);
            $payment_options[$this->name]['payment_controller_url'] = $this->context
                ->link
                ->getModuleLink($this->dependencies->name, 'payment', [
                    'type' => 'oney',
                    'io' => sprintf('%s', $split),
                ], true);
            $payment_options[$this->name]['logo'] = $this->img_path . 'oney/' . $oneyLogo;
            $payment_options[$this->name]['callToActionText'] = $oneyLabel;
            $payment_options[$this->name]['err_label'] = $err_label;
        }

        unset($payment_options['oney']);

        return $payment_options;
    }

    /**
     * @description Get cart page CTA
     *
     * @param false $active
     *
     * @return array
     */
    protected function getCartCallToAction($active = false)
    {
        if (!is_bool($active)) {
            $this->logger->addLog('OneyPaymentMethod::getCartCallToAction: Invalid parameter given, $active must be a boolean.');

            return [];
        }

        $img_path = $this->dependencies
            ->getPlugin()
            ->getConstant()
            ->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/img/oney/';

        return [
            'name' => 'oney_cart_animation',
            'image_url' => $img_path . $this->dependencies->name . '-cartOneyCta.jpg',
            'title' => $this->translation['oneyPopupCart']['title'],
            'switch' => true,
            'checked' => $active,
        ];
    }

    /**
     * @description Get Product page CTA
     *
     * @param false $active
     *
     * @return array
     */
    protected function getProductCallToAction($active = false)
    {
        if (!is_bool($active)) {
            $this->logger->addLog('OneyPaymentMethod::getProductCallToAction: Invalid parameter given, $active must be a boolean.');

            return [];
        }

        $img_path = $this->dependencies
            ->getPlugin()
            ->getConstant()
            ->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/img/oney/';

        return [
            'name' => 'oney_product_animation',
            'image_url' => $img_path . $this->dependencies->name . '-productOneyCta.jpg',
            'title' => $this->translation['oneyPopupProduct']['title'],
            'switch' => true,
            'checked' => $active,
        ];
    }

    /**
     * @description Get Oney schedule
     *
     * @param false $active
     *
     * @return array
     */
    protected function getSchedule($active = false)
    {
        if (!is_bool($active)) {
            $this->logger->addLog('OneyPaymentMethod::getSchedule: Invalid parameter given, $active must be a boolean.');

            return [];
        }

        $img_path = $this->dependencies
            ->getPlugin()
            ->getConstant()
            ->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/img/oney/';

        $iso_code = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get()->language->iso_code;

        $external_url = $this->dependencies
            ->getPlugin()
            ->getRoutes()
            ->getExternalUrl($iso_code);

        return [
            'name' => 'oney_schedule',
            'image_url' => $img_path . $this->dependencies->name . '-optimized.jpg',
            'title' => $this->translation['oneySchedule']['title'],
            'descriptions' => [[
                'description' => $this->translation['oneySchedule']['description'],
                'link_know_more' => [
                    'text' => $this->translation['link'],
                    'url' => $external_url['oney'] . '#h_2595dd3d-a281-43ab-a51a-4986fecde5ee',
                    'target' => '_blank',
                ],
            ]],
            'switch' => true,
            'checked' => $active,
        ];
    }

    /**
     * @description Get Oney Treshold
     *
     * @param array $current_configuration
     *
     * @return array
     */
    protected function getThresholds($current_configuration = [])
    {
        if (!is_array($current_configuration)) {
            $this->logger->addLog('OneyPaymentMethod::getThresholds: Invalid parameter given, $current_configuration must be an array.');

            return [];
        }

        $default_configuration = [
            'oney_min_amounts' => $current_configuration['oney_min_amounts'],
            'oney_max_amounts' => $current_configuration['oney_max_amounts'],
            'oney_custom_min_amounts' => $current_configuration['oney_custom_min_amounts'],
            'oney_custom_max_amounts' => $current_configuration['oney_custom_max_amounts'],
        ];

        $thresholds = [];
        foreach ($default_configuration as $key => $config) {
            $amount_key = str_replace('oney_', '', str_replace('_amounts', '', $key));

            if (!$config) {
                $thresholds[$amount_key] = 0;

                continue;
            }

            $amount = explode(':', $config);
            $amount = (int) $amount[1];
            $amount = $this->dependencies->getHelpers()['amount']->formatOneyAmount($amount)['result'];
            $thresholds[$amount_key] = $amount;
        }

        return [
            'name' => 'thresholds',
            'image_url' => $this->img_path . 'oney/' . $this->dependencies->name . '-thresholds.jpg',
            'title' => $this->translation['thresholds']['title'],
            'descriptions' => [
                'description' => $this->translation['thresholds']['description'],
                'min_amount' => [
                    'name' => 'oney_min_amounts',
                    'value' => $thresholds['custom_min'],
                    'placeholder' => $thresholds['custom_min'],
                    'default' => $thresholds['min'],
                ],
                'inter' => $this->translation['thresholds']['inter'],
                'max_amount' => [
                    'name' => 'oney_max_amounts',
                    'value' => $thresholds['custom_max'],
                    'placeholder' => $thresholds['custom_max'],
                    'default' => $thresholds['max'],
                ],
                'error' => [
                    'text' => sprintf(
                        $this->translation['thresholds']['error']['default'],
                        $thresholds['min'],
                        $thresholds['max']
                    ),
                    'maxtext' => sprintf(
                        $this->translation['thresholds']['error']['max'],
                        $thresholds['min'],
                        $thresholds['max']
                    ),
                    'mintext' => sprintf(
                        $this->translation['thresholds']['error']['min'],
                        $thresholds['min'],
                        $thresholds['max']
                    ),
                ],
            ],
            'switch' => false,
        ];
    }

    /**
     * @description Get error label for given error type
     *
     * @param string $error
     *
     * @return string
     */
    protected function getErrorLabel($error = '')
    {
        if (!is_string($error) || !$error) {
            return $this->dependencies->l('payplug.getPaymentOptions.errorOccurred', 'oneypaymentmethod');
        }

        switch ($error) {
            case 'invalid_addresses':
                $err_label =
                    $this->dependencies->l('payplug.getPaymentOptions.invalidAddresses', 'oneypaymentmethod');

                break;
            case 'invalid_amount_bottom':
            case 'invalid_amount_top':
                $limits = $this->dependencies->getPlugin()->getOney()->getOneyPriceLimit(true);
                $err_label = sprintf(
                    $this->dependencies->l('payplug.getPaymentOptions.invalidAmount', 'oneypaymentmethod'),
                    $this->dependencies->getHelpers()['amount']->formatOneyAmount($limits['min'])['result'],
                    $this->dependencies->getHelpers()['amount']->formatOneyAmount($limits['max'])['result']
                );

                break;
            case 'invalid_carrier':
                $err_label = $this->dependencies->l('payplug.getPaymentOptions.invalidCarrier', 'oneypaymentmethod');

                break;
            case 'invalid_cart':
                $err_label = $this->dependencies->l('payplug.getPaymentOptions.invalidCart', 'oneypaymentmethod');

                break;
            default:
                $err_label = $this->dependencies->l('payplug.getPaymentOptions.errorOccurred', 'oneypaymentmethod');

                break;
        }

        return $err_label;
    }

    /**
     * @description Set parameters for usage
     */
    protected function setParameters()
    {
        parent::setParameters();
        $this->translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getPaylaterTranslations();
    }
}
