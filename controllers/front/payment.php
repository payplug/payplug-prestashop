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
 * International Registered Trademark & Property of PayPlug SAS
 */
class PayplugPaymentModuleFrontController extends ModuleFrontController
{
    private $dependencies;
    private $logger;
    private $paymentClass;
    private $plugin;
    private $toolsAdapter;

    public function postProcess()
    {
        $this->dependencies = new \PayPlug\classes\DependenciesClass();
        $this->paymentClass = $this->dependencies->paymentClass;
        $this->plugin = $this->dependencies->getPlugin();
        $this->logger = $this->plugin->getLogger();
        $this->toolsAdapter = $this->plugin->getTools();

        $this->dependencies->apiClass->initializeApi();

        $context = Context::getContext();

        $type = Tools::getValue('type', null);
        $io = Tools::getValue('io', null);
        $is_oney = null;
        $with_fees = null;
        if ((isset($type)) && ($type == 'oney')) {
            if (isset($io)) {
                (bool) Configuration::get('PAYPLUG_ONEY_FEES') ? $with_fees = 'with_fees' : $with_fees = 'without_fees';
                $is_oney = 'x' . $io . '_' . $with_fees;
            }
        }
        $options = [
            'is_oney' => $is_oney,
            '_ajax' => 1,
        ];

        $payment_data = $this->paymentClass->preparePayment($options);
        $payment_data_16 = json_decode($payment_data, true);

        $page = Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order';
        $error_url = $context->link->getPageLink($page, true, $context->language->id, [
            'has_error' => 1,
            'step' => 3,
            'modulename' => 'payplug',
        ]);

        // Invalid payment then return error
        if (($payment_data['result'] && (isset($payment_data['return_url']) && $payment_data['return_url']))) {
            Tools::redirect($payment_data['return_url']);
        }
        if (($payment_data_16['result'] && (isset($payment_data_16['return_url']) && $payment_data_16['return_url']))) {
            Tools::redirect($payment_data_16['return_url']);
        } elseif (!$payment_data['result']) {
            if (isset($payment_data['response']) && $payment_data['response']) {
                $this->paymentClass->setPaymentErrorsCookie([$payment_data['response']]);
            }
            Tools::redirect($error_url);
        } elseif (!$payment_data_16['result']) {
            if (isset($payment_data_16['response']) && $payment_data_16['response']) {
                $this->paymentClass->setPaymentErrorsCookie([$payment_data_16['response']]);
            }
            Tools::redirect($error_url);
        }

        if ((isset($payment_data['response'])) || (isset($payment_data_16['response']))) {
            exit($payment_data['response']);
        }
    }
}
