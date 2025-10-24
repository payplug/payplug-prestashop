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

namespace PayPlug\src\application\adapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\classes\DependenciesClass;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class PrestashopAdapter17
{
    public $payplug;
    private $configuration;
    private $constant;
    private $context;
    private $dependencies;
    private $media;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
        $this->configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
        $this->media = $this->dependencies->getPlugin()->getMedia();
    }

    public function displayHeader()
    {
        $views_path = $this->constant->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/';
        $this->context->controller->addCSS($views_path . '/css/front-v' . $this->dependencies->version . '.css');
        $this->context->controller->addJS($views_path . '/js/utilities-v' . $this->dependencies->version . '.js');
        $this->context->controller->addJS($views_path . '/js/front-v' . $this->dependencies->version . '.js');
        $payment_methods = json_decode($this->dependencies->getPlugin()->getConfigurationClass()->getValue('payment_methods'), true);
        if ($this->dependencies->configClass->isValidFeature('feature_applepay')
            && (bool) $payment_methods['applepay']) {
            \Media::addJsDef(
                [
                    $this->dependencies->name . '_transaction_error_message' => $this->dependencies
                        ->getPlugin()
                        ->getPaymentAction()
                        ->renderPaymentErrors(
                            [
                                $this->dependencies
                                    ->getPlugin()
                                    ->getTranslationClass()
                                    ->l('payplug.prestashopspecific17.transactionNotCompleted', 'prestashopadapter17'),
                            ]
                        ),
                ]
            );
        }
    }

    /**
     * @description get the payment options
     *
     * @param $payment_options
     *
     * @return array
     */
    public function displayPaymentOption($payment_options)
    {
        $multi_account = json_decode($this->configuration->getValue('multi_account'), true);
        $currency = $this->context->currency;
        if ($this->dependencies->configClass->isValidFeature('feature_standard')
            && $this->dependencies->configClass->isValidFeature('feature_integrated')
            && array_key_exists('standard', $payment_options)
            && 'integrated' == (string) $this->configuration->getvalue('embedded_mode')
            && 'EUR' === $currency->iso_code
        ) {
            $payment_options = $this->dependencies
                ->getPlugin()
                ->getPaymentMethodClass()
                ->getPaymentMethod('standard')
                ->buildEmbeddedPaymentOption($payment_options, 'integrated');
        } elseif ($this->dependencies->configClass->isValidFeature('feature_standard')
            && !empty($multi_account['identifier_' . strtolower($currency->iso_code)])
            && array_key_exists('standard', $payment_options)
            && 'integrated' == (string) $this->configuration->getvalue('embedded_mode')
            && 'EUR' !== $currency->iso_code
        ) {
            $payment_options = $this->dependencies
                ->getPlugin()
                ->getPaymentMethodClass()
                ->getPaymentMethod('standard')
                ->buildEmbeddedPaymentOption($payment_options, 'hosted_fields');
        }

        $paymentOptions = [];
        foreach ($payment_options as $payment_option) {
            $payment_method = $payment_option['name'];
            $paymentOption = new PaymentOption();
            if (isset($payment_option['expiry_date_card'])) {
                $payment_option['callToActionText'] .= ' - ' . $payment_option['expiry_date_card'];
            }

            $paymentOption
                ->setLogo($payment_option['logo'])
                ->setCallToActionText($payment_option['callToActionText'])
                ->setModuleName($payment_option['moduleName'])
                ->setInputs($payment_option['inputs'])
            ;

            // No action for Apple Pay payments
            if (array_key_exists('action', $payment_option)) {
                $paymentOption->setAction($payment_option['action']);
            }

            // load oney schedule on e page loading
            if ('oney' == $payment_method && $payment_option['is_optimized']) {
                try {
                    $payment_schedule = $this->dependencies
                        ->getPlugin()
                        ->getPaymentMethodClass()
                        ->getPaymentMethod('oney')
                        ->getOneyPaymentOptionsList(
                            $payment_option['amount'],
                            $payment_option['iso_code']
                        );
                } catch (\Exception $e) {
                    // todo: set a permanent log
                    $payment_schedule = false;
                }

                if ($payment_schedule) {
                    $schedules = $this->dependencies
                        ->getPlugin()
                        ->getOneyAction()
                        ->renderSchedule(
                            $payment_schedule[$payment_option['type']],
                            $payment_option['amount']
                        );
                    $payment_option['additionalInformation'] = $schedules;
                }
            }

            if (isset($payment_option['additionalInformation'])) {
                $paymentOption->setAdditionalInformation($payment_option['additionalInformation']); // Échéanciers Oney
            }

            $paymentOptions[] = $paymentOption;
        }

        return $paymentOptions;
    }

    /**
     * @description Link to order by order state
     *
     * @param int $order_state
     *
     * @return string
     */
    public function getOrdersByStateLink($order_state)
    {
        return $this->context->link->getAdminLink(
            'AdminOrders',
            true,
            [],
            ['order[filters][osname]' => $order_state]
        );
    }

    public function assignSwitchConfiguration($configurations)
    {
        $switch = [];

        // defined if user is connected
        $connected = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.models.classes.merchant')
            ->isLogged();

        // show module to the customer
        $switch['show'] = [
            'name' => 'payplug_enable',
            'label' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.assignSwitchConfiguration.showPayplug', 'prestashopadapter17'),
            'active' => $connected,
            'small' => true,
            'checked' => $configurations['enable'],
        ];

        $switch['sandbox'] = [
            'name' => 'payplug_sandbox',
            'active' => $connected,
            'checked' => $configurations['sandbox_mode'],
            'label_left' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.assignSwitchConfiguration.test', 'prestashopadapter17'),
            'label_right' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.assignSwitchConfiguration.live', 'prestashopadapter17'),
        ];

        $switch['embedded'] = [
            'name' => 'payplug_embedded',
            'active' => $connected,
            'format' => true,
            'checked' => $configurations['embedded_mode'],
            'label_left' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.assignSwitchConfiguration.embedded', 'prestashopadapter17'),
            'label_center' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.assignSwitchConfiguration.popup', 'prestashopadapter17'),
            'label_right' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.assignSwitchConfiguration.redirected', 'prestashopadapter17'),
        ];

        $switch['one_click'] = [
            'name' => 'payplug_one_click',
            'active' => $connected,
            'checked' => $configurations['one_click'],
            'label_left' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.assignSwitchConfiguration.yes', 'prestashopadapter17'),
            'label_right' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.assignSwitchConfiguration.no', 'prestashopadapter17'),
        ];

        $switch['standard'] = [
            'name' => 'payplug_standard',
            'active' => $connected,
            'checked' => $configurations['standard'],
            'label_left' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.assignSwitchConfiguration.yes', 'prestashopadapter17'),
            'label_right' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.assignSwitchConfiguration.no', 'prestashopadapter17'),
        ];

        $switch['oney'] = [
            'name' => 'payplug_oney',
            'active' => $connected,
            'checked' => $configurations['oney'],
            'label_left' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.assignSwitchConfiguration.yes', 'prestashopadapter17'),
            'label_right' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.assignSwitchConfiguration.no', 'prestashopadapter17'),
        ];

        $switch['oney_optimized'] = [
            'name' => 'payplug_oney_optimized',
            'active' => true,
            'small' => true,
            'checked' => $configurations['oney_optimized'],
        ];
        $switch['oney_product_cta'] = [
            'name' => 'payplug_oney_product_cta',
            'active' => true,
            'small' => true,
            'checked' => $configurations['oney_product_cta'],
        ];
        $switch['oney_cart_cta'] = [
            'name' => 'payplug_oney_cart_cta',
            'active' => true,
            'small' => true,
            'checked' => $configurations['oney_cart_cta'],
        ];

        $switch['oney_fees'] = [
            'name' => 'payplug_oney_fees',
            'active' => true,
            'small' => true,
            'checked' => $configurations['oney_fees'],
        ];

        $switch['bancontact'] = [
            'name' => 'payplug_bancontact',
            'active' => $connected,
            'checked' => $configurations['bancontact'],
            'label_left' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.assignSwitchConfiguration.yes', 'prestashopadapter17'),
            'label_right' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.assignSwitchConfiguration.no', 'prestashopadapter17'),
        ];

        $switch['installment'] = [
            'name' => 'payplug_inst',
            'active' => $connected,
            'checked' => $configurations['installment'],
            'label_left' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.assignSwitchConfiguration.yes', 'prestashopadapter17'),
            'label_right' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.assignSwitchConfiguration.no', 'prestashopadapter17'),
        ];

        $switch['deferred'] = [
            'name' => 'payplug_deferred',
            'active' => $connected,
            'checked' => $configurations['deferred'],
            'label_left' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.assignSwitchConfiguration.yes', 'prestashopadapter17'),
            'label_right' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.assignSwitchConfiguration.no', 'prestashopadapter17'),
        ];

        $this->context->smarty->assign([
            'payplug_switch' => $switch,
        ]);
    }
}
