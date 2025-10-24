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

class StandardPaymentMethod extends PaymentMethod
{
    public function __construct($dependencies)
    {
        parent::__construct($dependencies);
        $this->name = 'standard';
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

        $option = parent::getOption($current_configuration);

        if (!is_array($current_configuration)) {
            $this->logger->addLog('StandardPaymentMethod::getOption: Invalid parameter given, $current_configuration must be an array.');

            return [];
        }

        $advanced_settings = [];

        $embedded_mode = [];
        if ($this->dependencies->configClass->isValidFeature('feature_integrated')) {
            $embedded_mode[] = [
                'name' => 'payplug_embedded',
                'label' => $this->translation['embedded']['options']['integrated'],
                'value' => 'integrated',
                'checked' => 'integrated' == $current_configuration['embedded_mode'],
            ];
        }
        $embedded_mode[] = [
            'name' => 'payplug_embedded',
            'label' => $this->translation['embedded']['options']['popup'],
            'value' => 'popup',
            'checked' => 'popup' == $current_configuration['embedded_mode'],
        ];
        $embedded_mode[] = [
            'name' => 'payplug_embedded',
            'label' => $this->translation['embedded']['options']['redirect'],
            'value' => 'redirect',
            'checked' => 'redirect' == $current_configuration['embedded_mode'],
        ];

        if ($this->dependencies->configClass->isValidFeature('feature_installment')) {
            $advanced_settings[] = [
                'name' => 'fractional',
                'title' => $this->translation['installment']['title'],
                'class' => '-installment',
                'enabled' => [
                    'name' => 'payplug_inst',
                    'checked' => $current_configuration['installment'],
                ],
                'descriptions' => [
                    'live' => [
                        'description_1' => $this->translation['installment']['descriptions']['description_1'],
                        'text_from' => $this->translation['installment']['descriptions']['text_from'],
                        'description_2' => $this->translation['installment']['descriptions']['description_2'],
                        'links' => [
                            [
                                'text' => $this->translation['installment']['descriptions']['controller_link'],
                                'url' => $this->link->getAdminLink('AdminPayPlugInstallment'),
                                'target' => '_blank',
                                'data_e2e' => 'data-panelInstallmentLink',
                            ],
                            [
                                'text' => $this->translation['installment']['link'],
                                'url' => $this->external_url['installments'],
                                'target' => '_blank',
                            ],
                        ],
                        'notes' => [
                            'type' => '-warning',
                            'description' => $this->translation['installment']['descriptions']['alert']['start']
                                . '<br />' . $this->translation['installment']['descriptions']['alert']['end'],
                        ],
                    ],
                    'sandbox' => [
                        'description_1' => $this->translation['installment']['descriptions']['description_1'],
                        'text_from' => $this->translation['installment']['descriptions']['text_from'],
                        'description_2' => $this->translation['installment']['descriptions']['description_2'],
                        'links' => [
                            [
                                'text' => $this->translation['installment']['descriptions']['controller_link'],
                                'url' => $this->link->getAdminLink('AdminPayPlugInstallment'),
                                'target' => '_blank',
                                'data_e2e' => 'data-panelInstallmentLink',
                            ],
                            [
                                'text' => $this->translation['installment']['link'],
                                'url' => $this->external_url['installments'],
                                'target' => '_blank',
                            ],
                        ],
                        'notes' => [
                            'type' => '-warning',
                            'description' => $this->translation['installment']['descriptions']['alert']['start']
                                . '<br />' . $this->translation['installment']['descriptions']['alert']['end'],
                        ],
                    ],
                ],
                'options' => [
                    [
                        'name' => 'payplug_inst_mode',
                        'type' => 'select',
                        'disabled' => !$current_configuration['installment'],
                        'options' => [
                            [
                                'value' => 2,
                                'label' => $this->translation['installment']['select']['2_schedules'],
                                'checked' => 2 == (int) $current_configuration['inst_mode'],
                            ],
                            [
                                'value' => 3,
                                'label' => $this->translation['installment']['select']['3_schedules'],
                                'checked' => 3 == (int) $current_configuration['inst_mode'],
                            ],
                            [
                                'value' => 4,
                                'label' => $this->translation['installment']['select']['4_schedules'],
                                'checked' => 4 == (int) $current_configuration['inst_mode'],
                            ],
                        ],
                    ],
                    [
                        'type' => 'input',
                        'name' => 'payplug_inst_min_amount',
                        'disabled' => !$current_configuration['installment'],
                        'value' => (int) $current_configuration['inst_min_amount'],
                        'min' => 4,
                        'step' => 1,
                        'max' => 20000,
                        'out_of_bound_msg' => $this->translation['installment']['error_limit'],
                    ],
                ],
                'notes' => [
                    'type' => '-warning',
                    'description' => $this->translation['installment']['descriptions']['alert'],
                ],
            ];
        }

        if ($this->dependencies->configClass->isValidFeature('feature_deferred')) {
            $advanced_settings[] = [
                'name' => 'deferred',
                'title' => $this->translation['deferred']['title'],
                'class' => '-deferred',
                'enabled' => [
                    'name' => 'payplug_deferred',
                    'checked' => $current_configuration['deferred'],
                ],
                'descriptions' => [
                    'live' => [
                        'description_1' => $this->translation['deferred']['descriptions']['description_1'],
                        'description_2' => $this->translation['deferred']['descriptions']['description_2'],
                        'links' => [
                            [
                                'text' => $this->translation['deferred']['link'],
                                'url' => $this->external_url['deferred'],
                                'target' => '_blank',
                            ],
                        ],
                    ],
                    'sandbox' => [
                        'description_1' => $this->translation['deferred']['descriptions']['description_1'],
                        'description_2' => $this->translation['deferred']['descriptions']['description_2'],
                        'links' => [
                            [
                                'text' => $this->translation['deferred']['link'],
                                'url' => $this->external_url['deferred'],
                                'target' => '_blank',
                            ],
                        ],
                    ],
                ],
                'options' => [
                    'disabled' => !$current_configuration['deferred'],
                    'name' => 'payplug_deferred_state',
                    'type' => 'select',
                    'options' => $this->getDeferredState((int) $current_configuration['deferred_state']),
                ],
            ];
        }

        $popup_description = $this->translation['embedded']['descriptions']['popup']['text'];
        $popup_description_link = '<a href="' . $this->external_url['portal'] . '" target="_blank">'
            . $this->translation['embedded']['descriptions']['popup']['link']
            . '</a>';
        $popup_description = str_replace('$popup_description_link', $popup_description_link, $popup_description);

        $redirect_description = $this->translation['embedded']['descriptions']['redirect']['text'];
        $redirect_description_link = '<a href="' . $this->external_url['portal'] . '" target="_blank">'
            . $this->translation['embedded']['descriptions']['redirect']['link']
            . '</a>';
        $redirect_description = str_replace('$redirect_description_link', $redirect_description_link, $redirect_description);

        $option['options'] = [
            [
                'type' => 'payment_option',
                'sub_type' => 'IOptions',
                'name' => 'embeded',
                'title' => $this->translation['embedded']['title'],
                'descriptions' => [
                    'live' => [
                        'description_popup' => $popup_description,
                        'description_redirect' => $redirect_description,
                        'description_integrated' => $this->translation['embedded']['descriptions']['integrated']['text'],
                        'link_know_more' => [
                            'text' => $this->translation['embedded']['link'],
                            'url' => $this->external_url['embedded'],
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description_popup' => $popup_description,
                        'description_redirect' => $redirect_description,
                        'description_integrated' => $this->translation['embedded']['descriptions']['integrated']['text'],
                        'link_know_more' => [
                            'text' => $this->translation['embedded']['link'],
                            'url' => $this->external_url['embedded'],
                            'target' => '_blank',
                        ],
                    ],
                ],
                'options' => $embedded_mode,
            ],
            [
                'type' => 'warning_message',
                'sub_type' => 'warning',
                'name' => 'warning_message',
                'payment_method' => 'integrated',
                'description_title' => $this->translation['integrated']['alert']['title'],
                'description' => $this->translation['integrated']['alert']['text'],
            ],
            [
                'type' => 'payment_option',
                'sub_type' => 'switch',
                'name' => 'one_click',
                'title' => $this->translation['one_click']['title'],
                'descriptions' => [
                    'live' => [
                        'description' => $this->translation['one_click']['descriptions']['live'],
                        'link_know_more' => [
                            'text' => $this->translation['one_click']['link'],
                            'url' => $this->external_url['one_click'],
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => $this->translation['one_click']['descriptions']['live'],
                        'link_know_more' => [
                            'text' => $this->translation['one_click']['link'],
                            'url' => $this->external_url['one_click'],
                            'target' => '_blank',
                        ],
                    ],
                ],
                'checked' => $current_configuration['one_click'],
            ],
        ];
        $option['advanced_settings'] = $advanced_settings ? [
            'title' => $this->translation['standard']['advanced'],
            'options' => $advanced_settings,
        ] : [];
        $option['descriptions']['live']['advanced_options'] = $this->translation['standard']['advanced'];
        $option['descriptions']['sandbox']['advanced_options'] = $this->translation['standard']['advanced'];

        return $option;
    }

    /**
     * @description Get order tab for given resource to create the order
     * @todo: add coverage to this method
     *
     * @param array $retrieve
     *
     * @return array
     */
    public function getOrderTab($retrieve = [])
    {
        $this->setParameters();

        $resource = $retrieve['resource'];
        if (null == $resource) {
            $this->logger->addLog('StandardPaymentMethod::getOrderTab() - Invalid argument given, $resource must be a non null object.');

            return [];
        }

        $order_tab = parent::getOrderTab($retrieve);

        if ($this->dependencies->getValidators()['payment']->isDeferred($resource)['result']) {
            $state_addons = $resource->is_live ? '' : '_test';
            $order_tab['order_state'] = $this->configuration->getValue('order_state_auth' . $state_addons);
        }

        return $order_tab;
    }

    /**
     * @description Get the payment tab required to generate a resource payment.
     * @todo: add coverage to this method
     *
     * @return array
     */
    public function getPaymentTab()
    {
        $payment_tab = parent::getPaymentTab();

        if (empty($payment_tab)) {
            return $payment_tab;
        }
        $payment_methods = $this->configuration->getValue('payment_methods');
        $payment_methods = json_decode($payment_methods, true);

        // Update if deferred payment is enable
        if (isset($payment_methods['deferred']) && $payment_methods['deferred']) {
            $payment_tab['authorized_amount'] = $payment_tab['amount'];
            unset($payment_tab['amount']);
        }

        // Update if current display is integrated
        if ('integrated' == (string) $this->configuration->getValue('embedded_mode') && !$this->tools->tool('getValue', 'hfToken')) {
            $payment_tab['integration'] = 'INTEGRATED_PAYMENT';
            unset($payment_tab['hosted_payment']['cancel_url']);
        }

        // Update payment card could be saved
        if (isset($payment_methods['one_click']) && $payment_methods['one_click']) {
            $cart_adapter = $this->dependencies
                ->getPlugin()
                ->getCart();
            $payment_tab['allow_save_card'] = !(bool) $cart_adapter->isGuestCartByCartId((int) $this->context->cart->id);
        }

        return $payment_tab;
    }

    /**
     * @description Generate and return correct resource return url
     *
     * todo: add coverage to this method
     *
     * @return array
     */
    public function getReturnUrl()
    {
        $this->setParameters();

        $return = parent::getReturnUrl();

        if (empty($return)) {
            return $return;
        }

        // Update if current display is integrated
        if ('integrated' == (string) $this->configuration->getValue('embedded_mode')) {
            $return['resource_id'] = $return['resource_stored']['resource_id'];
            $return['cart_id'] = (int) $this->context->cart->id;
        }

        // todo: getter of $_SERVER['HTTP_USER_AGENT'] should be in a service
        $regex_validator = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.validator.regex');

        $return['embedded'] = 'redirect' != (string) $this->configuration->getValue('embedded_mode')
            && !$regex_validator->isMobileDevice($_SERVER['HTTP_USER_AGENT'])['result'];

        unset($return['resource_stored']);

        return $return;
    }

    /**
     * @description Get the resource detail for order admin display
     * todo: add coverage to this method
     *
     * @param string $resource_id
     *
     * @return array
     */
    public function getResourceDetail($resource_id = '')
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('StandardPaymentMethod::getResourceDetail - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [];
        }

        $retrieve = $this->retrieve($resource_id);

        if (!$retrieve['result']) {
            $this->logger->addLog('StandardPaymentMethod::getResourceDetail - Payment resource can\'t be retrieved', 'error');

            return [];
        }
        $resource = $retrieve['resource'];

        $resource_details = parent::getResourceDetail($resource_id);

        if (!$this->dependencies->getValidators()['payment']->isDeferred($resource)['result']) {
            return $resource_details;
        }

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getOrderTranslations();

        $can_be_captured = !$this->dependencies->getValidators()['payment']->isFailed($resource)['result']
            && !(bool) $resource->is_paid
            && !$this->dependencies->getValidators()['payment']->isExpired($resource)['result'];

        $resource_details['can_be_captured'] = $can_be_captured;
        $resource_details['authorization'] = true;

        if ((bool) $resource->is_paid) {
            $resource_details['date'] = date('d/m/Y', $resource->paid_at);
            $resource_details['status_message'] = '(' . $translation['detail']['capture']['deferred'] . ')';
        } else {
            $expiration = date('d/m/Y', $resource->authorization->expires_at);
            if ($can_be_captured) {
                $resource_details['status_message'] = sprintf(
                    '(' . $translation['detail']['capture']['expiration'] . ')',
                    $expiration
                );
                $resource_details['date'] = date('d/m/Y', $resource->authorization->authorized_at);
                $resource_details['date_expiration'] = $expiration;
                $resource_details['expiration_display'] = sprintf(
                    $translation['detail']['capture']['warning'],
                    $expiration
                );
            } elseif (isset($resource->authorization->authorized_at) && (bool) $resource->authorization->authorized_at) {
                $resource_details['date'] = date('d/m/Y', $resource->authorization->authorized_at);
            }
        }

        return $resource_details;
    }

    /**
     * @description Get the current payment status
     * @todo: add coverage to this method
     *
     * @param null $resource
     *
     * @return array
     */
    public function getPaymentStatus($resource = null)
    {
        $this->setParameters();

        if (!is_object($resource) || !$resource) {
            $this->logger->addLog('StandardPaymentMethod::getPaymentStatus() - Invalid argument given, $resource must be a non null object.');

            return [];
        }

        $status = parent::getPaymentStatus($resource);
        if (in_array($status['code'], ['paid', 'abandoned', 'failed', 'refunded', 'partially_refunded'])) {
            return $status;
        }

        if ((bool) $resource->authorization && ($resource->authorization->expires_at - time()) > 0) {
            return [
                'id_status' => 8,
                'code' => 'authorized',
            ];
        }

        if ((bool) $resource->authorization && ($resource->authorization->expires_at - time()) <= 0) {
            return [
                'id_status' => 9,
                'code' => 'authorization_expired',
            ];
        }

        return $status;
    }

    /**
     * @description Retrieve the resource for a given resource id.
     *
     * @param string $resource_id
     *
     * @return array
     */
    public function retrieve($resource_id = '')
    {
        $this->setParameters();

        if ((!is_string($resource_id) || !$resource_id) && !is_array($resource_id)) {
            $this->logger->addLog('PaymentMethod::retrieve - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [
                'code' => 500,
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ];
        }

        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('resource_id', $resource_id);

        // If stored resource can't be found, we check if given resource id is from schedule
        if (!$stored_resource) {
            $stored_resource = $this->dependencies
                ->getPlugin()
                ->getPaymentRepository()
                ->getFromSchedule($resource_id);
        }

        if (!$stored_resource) {
            $this->logger->addLog('PaymentMethod::retrieve - Can\'t find stored payment from given resource id.', 'error');

            return [
                'code' => 500,
                'result' => false,
                'message' => 'Can\'t find stored payment from given resource id',
            ];
        }

        // We retrieve the payment from the stored payment configuration
        $is_live = isset($stored_resource['is_live']) && (bool) $stored_resource['is_live'];
        $this->api_service->initialize((bool) $is_live);
        $is_hosted_fields = 0 !== strpos($resource_id, 'pay_');
        if ($is_hosted_fields) {
            $resource_id = $this->BuildRetrieveHostedFieldsData($resource_id);
        }
        $retrieve = $this->api_service->retrievePayment($resource_id, $is_hosted_fields);
        if ($retrieve['result']) {
            $retrieve['resource']->is_live = $is_live;
        } else {
            // If we don't find the payment, for retrocompatibility we switch the mode then try again
            // This section could be removed for highter module version
            $this->api_service->initialize(!(bool) $is_live);
            $retrieve = $this->api_service->retrievePayment($resource_id);
        }

        return $retrieve;
    }

    /**
     * @description build retrieve hostedFields data
     *
     * @param $resource_id
     *
     * @return array
     */
    public function BuildRetrieveHostedFieldsData($resource_id)
    {
        $this->setParameters();
        $multi_account = json_decode($this->configuration->getValue('multi_account'), true);
        if (isset($multi_account) && !empty($multi_account)) {
            $identifier = $multi_account['identifier_' . strtolower($this->context->currency->iso_code)];
        } else {
            return [];
        }

        $hosted_fields_data['method'] = 'getTransactions';
        $hosted_fields_data['params']['IDENTIFIER'] = $identifier;
        $hosted_fields_data['params']['OPERATIONTYPE'] = 'getTransaction';
        $hosted_fields_data['params']['TRANSACTIONID'] = $resource_id;
        $hosted_fields_data['params']['VERSION'] = '3.0';
        $hosted_fields_data['params']['HASH'] = $this->buildHashContent($hosted_fields_data['params'], true);

        return $hosted_fields_data;
    }

    /**
     * Build Payment options  for integrated payment or hosted fields.
     *
     * @param array $payment_options
     * @param string $mode 'integrated'|'hosted_fields'
     *
     * @return array
     */
    public function buildEmbeddedPaymentOption($payment_options, $mode)
    {
        if (empty($payment_options) || !isset($payment_options['standard'])) {
            return $payment_options;
        }
        $payment_data = [
            'integrated' => [
                'name' => 'integrated',
                'action' => 'javascript:payplugModule.integrated.form.validate();',
                'tpl' => 'integrated_payment.tpl',
                'additionalTpl' => 'checkout/payment/integrated_payment.tpl',
                'jsUrl' => 'integrated_payment_js_url',
                'extra_classes' => 'payplug integrated',
            ],
            'hosted_fields' => [
                'name' => 'hosted_fields',
                'action' => 'javascript:payplugModule.hosted_fields.form.validate();',
                'tpl' => 'hosted_fields.tpl',
                'additionalTpl' => 'checkout/payment/hosted_fields.tpl',
                'jsUrl' => 'hosted_fields_js_url',
                'extra_classes' => 'payplug hosted_fields',
            ],
        ];
        if (!isset($payment_data[$mode])) {
            return $payment_options;
        }

        $embedded_option = $payment_data[$mode];
        $js_url = $this->dependencies->getPlugin()->getRoutes()->loadEmbeddedJsUrl($mode);
        $translation = $this->dependencies->getPlugin()->getTranslationClass()->getFrontIntegratedPaymentTranslations();
        $privacyLink = $this->getPrivacyLink();
        $payment_methods = json_decode($this->dependencies->getPlugin()->getConfigurationClass()->getValue('payment_methods'), true);
        $this->context->smarty->assign([
            $embedded_option['jsUrl'] => $js_url,
            'is_one_click_activated' => !empty($payment_methods['one_click']),
            'is_deferred_activated' => !empty($payment_methods['deferred']),
            'placeholderCardholder' => $this->dependencies->getPlugin()->getTranslationClass()->l('specific17.setIntegratedPaymentOption.placeholderCardholder', 'prestashopadapter17'),
            'placeholderPan' => $this->dependencies->getPlugin()->getTranslationClass()->l('specific17.setIntegratedPaymentOption.placeholderPan', 'prestashopadapter17'),
            'placeholderExp' => $this->dependencies->getPlugin()->getTranslationClass()->l('specific17.setIntegratedPaymentOption.placeholderExp', 'prestashopadapter17'),
            'placeholderCvv' => $this->dependencies->getPlugin()->getTranslationClass()->l('specific17.setIntegratedPaymentOption.placeholderCvv', 'prestashopadapter17'),
            'privacy' => isset($translation['privacy']) ? $translation['privacy'] : '',
            'secure' => isset($translation['secure']) ? $translation['secure'] : '',
            'privacyLink' => $privacyLink,
        ]);
        $option = [
            'name' => $embedded_option['name'],
            'inputs' => [
                'method' => [
                    'name' => 'method',
                    'type' => 'hidden',
                    'value' => $embedded_option['name'],
                ],
            ],
            'action' => $embedded_option['action'],
            'logo' => $payment_options['standard']['logo'],
            'moduleName' => 'payplug',
            'callToActionText' => $this->dependencies->getPlugin()->getTranslationClass()->l('specific17.setIntegratedPaymentOption.name', 'prestashopadapter17'),
            'tpl' => $embedded_option['tpl'],
            'extra_classes' => $embedded_option['extra_classes'],
            'additionalInformation' => $this->dependencies->configClass->fetchTemplate($embedded_option['additionalTpl']),
        ];
        $payment_options['standard'] = $option;

        return $payment_options;
    }

    /**
     * @description Get deffered state for configuration usage
     *
     * @param int $deferred_state
     *
     * @return array
     */
    protected function getDeferredState($deferred_state = 0)
    {
        if (!is_int($deferred_state)) {
            return [];
        }

        $order_states = $this->dependencies
            ->orderClass
            ->getOrderStates();

        $order_states_values = [
            0 => [
                'value' => 0,
                'label' => $this->translation['deferred']['states']['default'],
                'checked' => (int) $deferred_state ? false : true,
            ],
        ];
        if ($order_states) {
            foreach ($order_states as $order_state) {
                $order_states_values[$order_state['id_order_state']] = [
                    'value' => $order_state['id_order_state'],
                    'label' => sprintf(
                        $this->translation['deferred']['states']['state'],
                        $order_state['name']
                    ),
                    'checked' => $order_state['id_order_state'] == $deferred_state ? true : false,
                    'warning_msg' => sprintf(
                        $this->translation['deferred']['states']['alert'],
                        $order_state['name']
                    ),
                ];
            }
        }
        ksort($order_states_values);

        return (array) $order_states_values;
    }

    /**
     * @param mixed $payment_options
     *
     * @return array
     */
    protected function getPaymentOption($payment_options = [])
    {
        if (!is_array($payment_options)) {
            return [];
        }

        $has_saved_card = false;
        if (!empty($payment_options)) {
            foreach ($payment_options as $key => $payment_option) {
                if (!$has_saved_card && false !== strpos($key, 'one_click')) {
                    $has_saved_card = true;
                }
            }
        }

        $payment_options = parent::getPaymentOption($payment_options);

        if (!isset($payment_options[$this->name])) {
            return $payment_options;
        }

        $payment_options[$this->name]['callToActionText'] = $has_saved_card
            ? $this->translation[$this->name]['has_saved_card']
            : $payment_options[$this->name]['callToActionText'];
        $payment_options[$this->name]['extra_classes'] = 'payplug default';
        $payment_options[$this->name]['logo'] = $this->img_path
            . 'svg/checkout/standard/logos_schemes_'
            . $this->dependencies->configClass->getImgLang() . '.svg';

        return $payment_options;
    }

    /**
     * build privacy policy link.
     */
    protected function getPrivacyLink()
    {
        $this->setParameters();
        $iso = isset($this->context->language->iso_code) ? $this->context->language->iso_code : '';

        switch ($iso) {
            case 'fr':
                return 'https://www.payplug.com/fr/politique-de-confidentialite/';

            case 'it':
                return 'https://www.payplug.com/it/politica-di-confidenzialita/';

            default:
                return 'https://www.payplug.com/privacy-policy/';
        }
    }
}
