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
        $payment_options[$this->name]['callToActionText'] = $has_saved_card
            ? $this->translation[$this->name]['has_saved_card']
            : $payment_options[$this->name]['callToActionText'];
        $payment_options[$this->name]['extra_classes'] = 'payplug default';
        $payment_options[$this->name]['logo'] = $this->img_path
            . 'svg/checkout/standard/logos_schemes_'
            . $this->dependencies->configClass->getImgLang() . '.svg';

        return $payment_options;
    }
}
