<?php
/**
 * 2013 - 2022 PayPlug SAS
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
 * @author    PayPlug SAS
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\application\adapter;

use Language;
use Media;
use PayPlug\classes\DependenciesClass;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use Symfony\Component\Dotenv\Dotenv;
use Tab;
use Tools;
use Validate;

class PrestashopAdapter17
{
    public $payplug;
    private $config;
    private $constant;
    private $context;
    private $dependencies;
    private $oney;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
        $this->config = $this->dependencies->getPlugin()->getConfiguration();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
        $this->module = $this->dependencies->getPlugin()->getModule()->getInstanceByName($this->dependencies->name);
        $this->oney = $this->dependencies->getPlugin()->getOney();
        $this->paymentClass = $this->dependencies->paymentClass;
    }

    public function displayHeader()
    {
        $views_path = $this->constant->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/';
        $this->context->controller->addCSS($views_path . '/css/front-v' . $this->dependencies->version . '.css');
        $this->context->controller->addJS($views_path . '/js/utilities-v' . $this->dependencies->version . '.js');
        $this->context->controller->addJS($views_path . '/js/front-v' . $this->dependencies->version . '.js');
        if ($this->dependencies->configClass->isValidFeature('feature_applepay')
            && (bool) $this->config->get($this->dependencies->getConfigurationKey('applepay')) === true) {
            Media::addJsDef(
                [
                    $this->dependencies->name . '_transaction_error_message' => $this->paymentClass->displayPaymentErrors(
                        [
                            $this->dependencies->l('payplug.prestashopspecific17.transactionNotCompleted', 'prestashopadapter17'),
                        ]
                    ),
                ]
            );

            $this->context->controller->addJS($views_path . 'js/applepay-v' . $this->dependencies->version . '.js');
        }
    }

    public function displayPaymentOption($payment_options)
    {
        if ($this->dependencies->configClass->isValidFeature('feature_integrated')
            && (string) $this->config->get(
                $this->dependencies->getConfigurationKey('embeddedMode')
            ) == 'integrated'
            && $this->dependencies->configClass->isValidFeature('feature_standard')
        ) {
            $payment_options = $this->setIntegratedPaymentOption($payment_options);
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
            if ($payment_method == 'oney' && $payment_option['is_optimized']) {
                try {
                    $payment_schedule = $this->oney->getOneyPaymentOptionsList(
                        $payment_option['amount'],
                        $payment_option['iso_code']
                    );
                } catch (\Exception $e) {
                    // todo: set a permanent log
                    $payment_schedule = false;
                }

                if ($payment_schedule) {
                    $schedules = $this->oney->displayOneySchedule(
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
     * @description  creation payment option
     * for integreated payment
     *
     * @param $payment_options
     *
     * @return mixed
     */
    public function setIntegratedPaymentOption($payment_options)
    {
        $dotenv = new Dotenv();
        $dotenvFile = dirname(__FILE__, 4) . '/payplugroutes/.env';
        if (file_exists($dotenvFile)) {
            $dotenv->load($dotenvFile);
            $integrated_payment_js_url = $_ENV['INTEGRATED_PAYMENT_DOMAIN'];
        } else {
            $integrated_payment_js_url = 'https://cdn.payplug.com/js/integrated-payment/v0/index.js';
        }
        $integrated = [];
        $integrated['name'] = 'integrated';
        $integrated['inputs']['method'] = [
            'name' => 'method',
            'type' => 'hidden',
            'value' => 'integrated',
        ];

        $integrated['action'] = 'javascript:payplugModule.integrated.form.getIntPaymentId();';
        $integrated['logo'] = $payment_options['standard']['logo'];
        $integrated['moduleName'] = 'payplug';
        $integrated['callToActionText'] = $this->dependencies->l('specific17.setIntegratedPaymentOption.name', 'prestashopadapter17');
        $integrated['tpl'] = 'integrated_payment.tpl';
        $integrated['extra_classes'] = 'payplug integrated';
        $this->context->smarty->assign([
            'integrated_payment_js_url' => $integrated_payment_js_url,
            'is_one_click_activated' => (bool) $this->config->get(
                $this->dependencies->getConfigurationKey('oneClick')
            ),
            'is_deferred_activated' => (bool) $this->config->get(
                $this->dependencies->getConfigurationKey('deferred')
            ),
            'placeholderCardholder' => $this->dependencies->l('specific17.setIntegratedPaymentOption.placeholderCardholder', 'prestashopadapter17'),
            'placeholderPan' => $this->dependencies->l('specific17.setIntegratedPaymentOption.placeholderPan', 'prestashopadapter17'),
            'placeholderExp' => $this->dependencies->l('specific17.setIntegratedPaymentOption.placeholderExp', 'prestashopadapter17'),
            'placeholderCvv' => $this->dependencies->l('specific17.setIntegratedPaymentOption.placeholderCvv', 'prestashopadapter17'),
        ]);

        $integrated['additionalInformation'] =
            $this->dependencies->configClass->fetchTemplate('checkout/payment/integrated_payment.tpl');

        $payment_options['standard'] = $integrated;

        return $payment_options;
    }

    // todo: set Tab install process in a adapter
    public function installTab()
    {
        $installed = true;

        if (isset($this->module->adminControllers) && !empty($this->module->adminControllers)) {
            foreach ($this->module->adminControllers as $adminController) {
                if (Tab::getIdFromClassName($adminController['className'])) {
                    continue;
                }

                $tab = new Tab();

                if (isset($adminController['name'])) {
                    foreach (Language::getLanguages(false) as $language) {
                        $id_lang = (int) $language['id_lang'];
                        $iso_code = Tools::strtolower($language['iso_code']);
                        if (isset($adminController['name'][$iso_code])) {
                            $tab->name[$id_lang] = $adminController['name'][$iso_code];
                        } else {
                            $tab->name[$id_lang] = $adminController['name']['en'];
                        }
                    }
                } else {
                    $tab->name = array_fill_keys(Language::getIDs(false), $this->module->displayName);
                }

                if (isset($adminController['parent'])) {
                    if (is_int($adminController['parent'])) {
                        $tab->id_parent = $adminController['parent'];
                    } else {
                        $tab->id_parent = Tab::getIdFromClassName($adminController['parent']);
                    }
                }

                $tab->class_name = $adminController['className'];
                $tab->active = true;
                $tab->module = $this->module->name;
                $installed = $installed && $tab->add();
            }
        }

        return $installed;
    }

    // todo: set Tab uninstall process in a adapter
    public function uninstallTab()
    {
        $flag = true;

        if (isset($this->module->adminControllers) && !empty($this->module->adminControllers)) {
            foreach ($this->module->adminControllers as $adminController) {
                if ($idTab = Tab::getIdFromClassName($adminController['className'])) {
                    $tab = new Tab($idTab);
                    $flag = $flag && $tab->delete();
                    unset($idTab);
                }
            }
        }

        return $flag;
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
        $connected = !empty($configurations['email'])
            && (!empty($configurations['test_api_key'])
                || !empty($configurations['live_api_key']));

        // show module to the customer
        $switch['show'] = [
            'name' => 'payplug_show',
            'label' => $this->dependencies->l('payplug.assignSwitchConfiguration.showPayplug', 'prestashopadapter17'),
            'active' => $connected,
            'small' => true,
            'checked' => $configurations['show'],
        ];

        $switch['sandbox'] = [
            'name' => 'payplug_sandbox',
            'active' => $connected,
            'checked' => $configurations['sandbox_mode'],
            'label_left' => $this->dependencies->l('payplug.assignSwitchConfiguration.test', 'prestashopadapter17'),
            'label_right' => $this->dependencies->l('payplug.assignSwitchConfiguration.live', 'prestashopadapter17'),
        ];

        $switch['embedded'] = [
            'name' => 'payplug_embedded',
            'active' => $connected,
            'format' => true,
            'checked' => $configurations['embedded_mode'],
            'label_left' => $this->dependencies->l('payplug.assignSwitchConfiguration.embedded', 'prestashopadapter17'),
            'label_center' => $this->dependencies->l('payplug.assignSwitchConfiguration.popup', 'prestashopadapter17'),
            'label_right' => $this->dependencies->l('payplug.assignSwitchConfiguration.redirected', 'prestashopadapter17'),
        ];

        $switch['one_click'] = [
            'name' => 'payplug_one_click',
            'active' => $connected,
            'checked' => $configurations['one_click'],
            'label_left' => $this->dependencies->l('payplug.assignSwitchConfiguration.yes', 'prestashopadapter17'),
            'label_right' => $this->dependencies->l('payplug.assignSwitchConfiguration.no', 'prestashopadapter17'),
        ];

        $switch['standard'] = [
            'name' => 'payplug_standard',
            'active' => $connected,
            'checked' => $configurations['standard'],
            'label_left' => $this->dependencies->l('payplug.assignSwitchConfiguration.yes', 'prestashopadapter17'),
            'label_right' => $this->dependencies->l('payplug.assignSwitchConfiguration.no', 'prestashopadapter17'),
        ];

        $switch['oney'] = [
            'name' => 'payplug_oney',
            'active' => $connected,
            'checked' => $configurations['oney'],
            'label_left' => $this->dependencies->l('payplug.assignSwitchConfiguration.yes', 'prestashopadapter17'),
            'label_right' => $this->dependencies->l('payplug.assignSwitchConfiguration.no', 'prestashopadapter17'),
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
            'label_left' => $this->dependencies->l('payplug.assignSwitchConfiguration.yes', 'prestashopadapter17'),
            'label_right' => $this->dependencies->l('payplug.assignSwitchConfiguration.no', 'prestashopadapter17'),
        ];

        $switch['installment'] = [
            'name' => 'payplug_inst',
            'active' => $connected,
            'checked' => $configurations['inst'],
            'label_left' => $this->dependencies->l('payplug.assignSwitchConfiguration.yes', 'prestashopadapter17'),
            'label_right' => $this->dependencies->l('payplug.assignSwitchConfiguration.no', 'prestashopadapter17'),
        ];

        $switch['deferred'] = [
            'name' => 'payplug_deferred',
            'active' => $connected,
            'checked' => $configurations['deferred'],
            'label_left' => $this->dependencies->l('payplug.assignSwitchConfiguration.yes', 'prestashopadapter17'),
            'label_right' => $this->dependencies->l('payplug.assignSwitchConfiguration.no', 'prestashopadapter17'),
        ];

        $this->context->smarty->assign([
            'payplug_switch' => $switch,
        ]);
    }

    /**
     * @description Check if string is Plaintext Password
     *
     * @param $plaintextPasswd
     * @param int $size
     *
     * @return bool
     */
    public function isPlaintextPassword($plaintextPasswd, $size = 5)
    {
        return Validate::isPlaintextPassword($plaintextPasswd);
    }
}
