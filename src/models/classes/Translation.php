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

namespace PayPlug\src\models\classes;

class Translation
{
    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @return array
     */
    public function getFooterTranslations()
    {
        return [
            'button' => [
                'text' => $this->dependencies->l('footer.button.text', 'translation'),
            ],
            'faq' => [
                'top' => $this->dependencies->l('footer.faq.top', 'translation'),
                'bottom' => $this->dependencies->l('footer.faq.bottom', 'translation'),
                'link' => $this->dependencies->l('footer.faq.link', 'translation'),
                'link_url' => $this->dependencies->l('footer.faq.link_url', 'translation'),
            ],
        ];
    }

    /**
     * @return array
     */
    public function getHeaderTranslations()
    {
        return [
            'hidden' => $this->dependencies->l('payplug.getHeaderTranslations.headerHidden', 'translation'),
            'visible' => $this->dependencies->l('payplug.getHeaderTranslations.headerVisible', 'translation'),
            'title' => $this->dependencies->l('payplug.getHeaderTranslations.headerTitle', 'translation'),
            'text' => $this->dependencies->l('payplug.getHeaderTranslations.headerText', 'translation'),
        ];
    }

    /**
     * @return array
     */
    public function getLoginTranslations()
    {
        return [
            'title' => $this->dependencies->l('login.title', 'translation'),
            'description' => $this->dependencies->l('login.description', 'translation'),
            'email' => $this->dependencies->l('login.email', 'translation'),
            'password' => $this->dependencies->l('login.password', 'translation'),
            'register' => $this->dependencies->l('login.register', 'translation'),
            'connect' => $this->dependencies->l('login.connect', 'translation'),
            'forgot_password' => $this->dependencies->l('login.forgot_password', 'translation'),
            'login_error' => $this->dependencies->l('login.error', 'translation'),
        ];
    }

    /**
     * @return array
     */
    public function getLoggedTranslations()
    {
        return [
            'title' => $this->dependencies->l('logged.title', 'translation'),
            'description' => $this->dependencies->l('logged.description', 'translation'),
            'user' => [
                'link' => $this->dependencies->l('logged.user.link', 'translation'),
                'logout' => $this->dependencies->l('logged.user.logout', 'translation'),
            ],
            'mode' => [
                'title' => $this->dependencies->l('logged.mode.title', 'translation'),
                'description' => [
                    'live' => $this->dependencies->l('logged.mode.description.live', 'translation'),
                    'sandbox' => $this->dependencies->l('logged.mode.description.sandbox', 'translation'),
                ],
                'link' => [
                    'live' => $this->dependencies->l('logged.mode.link.live', 'translation'),
                    'sandbox' => $this->dependencies->l('logged.mode.link.sandbox', 'translation'),
                ],
                'options' => [
                    'live' => $this->dependencies->l('logged.mode.options.live', 'translation'),
                    'sandbox' => $this->dependencies->l('logged.mode.options.sandbox', 'translation'),
                ],
            ],
            'inactive' => [
                'modal' => [
                    'title' => $this->dependencies->l('logged.inactive.modal.title', 'translation'),
                    'description' => $this->dependencies->l('logged.inactive.modal.description', 'translation'),
                    'password_label' => $this->dependencies->l('logged.inactive.modal.password_label', 'translation'),
                    'cancel' => $this->dependencies->l('logged.inactive.modal.cancel', 'translation'),
                    'ok' => $this->dependencies->l('logged.inactive.modal.ok', 'translation'),
                    'error' => $this->dependencies->l('logged.inactive.modal.error', 'translation'),
                ],
                'account' => [
                    'warning' => [
                        'title' => $this->dependencies->l('logged.inactive.account.warning.title', 'translation'),
                        'description' => $this->dependencies->l('logged.inactive.account.warning.description', 'translation'),
                        'link' => $this->dependencies->l('logged.inactive.account.warning.link', 'translation'),
                        'trigger' => $this->dependencies->l('logged.inactive.account.warning.trigger', 'translation'),
                    ],
                    'error' => [
                        'title' => $this->dependencies->l('logged.inactive.account.error.title', 'translation'),
                        'description' => $this->dependencies->l('logged.inactive.account.error.description', 'translation'),
                    ],
                    'success' => [
                        'title' => $this->dependencies->l('logged.inactive.account.success.title', 'translation'),
                        'description' => $this->dependencies->l('logged.inactive.account.success.description', 'translation'),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getSubscribeTranslations()
    {
        return [
            'title' => $this->dependencies->l('subscribe.title', 'translation'),
            'description' => $this->dependencies->l('subscribe.description', 'translation'),
            'text' => $this->dependencies->l('subscribe.text', 'translation'),
            'register' => $this->dependencies->l('subscribe.register', 'translation'),
            'connect' => $this->dependencies->l('subscribe.connect', 'translation'),
        ];
    }

    /**
     * @todo: Dispatch the different payment methods translation in their function
     *
     * @return array
     */
    public function getPaymentMethodsTranslations()
    {
        return [
            'title' => $this->dependencies->l('paymentmethods.title', 'translation'),
            'description' => $this->dependencies->l('paymentmethods.description', 'translation'),
            'standard' => [
                'title' => $this->dependencies->l('paymentmethods.standard.title', 'translation'),
                'descriptions' => [
                    'live' => $this->dependencies->l('paymentmethods.standard.descriptions.live', 'translation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.standard.link', 'translation'),
                'advanced' => $this->dependencies->l('paymentmethods.standard.advanced', 'translation'),
                'call_to_action' => $this->dependencies->l('paymentmethods.standard.call_to_action', 'translation'),
                'has_saved_card' => $this->dependencies->l('paymentmethods.standard.has_saved_card', 'translation'),
            ],
            'embedded' => [
                'title' => $this->dependencies->l('paymentmethods.embedded.title', 'translation'),
                'descriptions' => [
                    'integrated' => [
                        'text' => $this->dependencies->l('paymentmethods.embedded.descriptions.integrated.text', 'translation'),
                    ],
                    'popup' => [
                        'text' => $this->dependencies->l('paymentmethods.embedded.descriptions.popup.text', 'translation'),
                        'link' => $this->dependencies->l('paymentmethods.embedded.descriptions.popup.link', 'translation'),
                    ],
                    'redirect' => [
                        'text' => $this->dependencies->l('paymentmethods.embedded.descriptions.redirect.text', 'translation'),
                        'link' => $this->dependencies->l('paymentmethods.embedded.descriptions.redirect.link', 'translation'),
                    ],
                ],
                'link' => $this->dependencies->l('paymentmethods.embedded.link', 'translation'),
                'options' => [
                    'integrated' => $this->dependencies->l('paymentmethods.embedded.options.integrated', 'translation'),
                    'popup' => $this->dependencies->l('paymentmethods.embedded.options.popup', 'translation'),
                    'redirect' => $this->dependencies->l('paymentmethods.embedded.options.redirect', 'translation'),
                ],
            ],
            'integrated' => [
                'alert' => [
                    'title' => $this->dependencies->l('paymentmethods.integrated.alert.text.title', 'translation'),
                    'text' => $this->dependencies->l('paymentmethods.integrated.alert.text', 'translation'),
                ],
            ],
            'one_click' => [
                'title' => $this->dependencies->l('paymentmethods.one_click.title', 'translation'),
                'descriptions' => [
                    'live' => $this->dependencies->l('paymentmethods.one_click.descriptions.live', 'translation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.one_click.link', 'translation'),
                'call_to_action' => $this->dependencies->l('paymentmethods.one_click.call_to_action', 'translation'),
            ],

            'installment' => [
                'title' => $this->dependencies->l('paymentmethods.installment.title', 'translation'),
                'descriptions' => [
                    'description_1' => $this->dependencies->l('paymentmethods.installment.descriptions.description_1', 'translation'),
                    'text_from' => $this->dependencies->l('paymentmethods.installment.descriptions.text_from', 'translation'),
                    'description_2' => $this->dependencies->l('paymentmethods.installment.descriptions.description_2', 'translation'),
                    'controller_link' => $this->dependencies->l('paymentmethods.installment.descriptions.controller_link', 'translation'),
                    'alert' => [
                        'start' => $this->dependencies->l('paymentmethods.installment.descriptions.alert.start', 'translation'),
                        'end' => $this->dependencies->l('paymentmethods.installment.descriptions.alert.end', 'translation'),
                    ],
                ],
                'select' => [
                    '2_schedules' => $this->dependencies->l('paymentmethods.installment.select.2_schedules', 'translation'),
                    '3_schedules' => $this->dependencies->l('paymentmethods.installment.select.3_schedules', 'translation'),
                    '4_schedules' => $this->dependencies->l('paymentmethods.installment.select.4_schedules', 'translation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.installment.link', 'translation'),
                'error_limit' => $this->dependencies->l('paymentmethods.installment.error_limit', 'translation'),
                'call_to_action' => $this->dependencies->l('paymentmethods.installment.call_to_action', 'translation'),
            ],
            'deferred' => [
                'title' => $this->dependencies->l('paymentmethods.deferred.title', 'translation'),
                'descriptions' => [
                    'description_1' => $this->dependencies->l('paymentmethods.deferred.descriptions.description_1', 'translation'),
                    'description_2' => $this->dependencies->l('paymentmethods.deferred.descriptions.description_2', 'translation'),
                ],
                'states' => [
                    'default' => $this->dependencies->l('paymentmethods.deferred.states.default', 'translation'),
                    'state' => $this->dependencies->l('paymentmethods.deferred.states.state', 'translation'),
                    'alert' => $this->dependencies->l('paymentmethods.deferred.states.alert', 'translation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.deferred.link', 'translation'),
            ],
            'amex' => [
                'title' => $this->dependencies->l('paymentmethods.amex.title', 'translation'),
                'descriptions' => [
                    'live' => $this->dependencies->l('paymentmethods.amex.descriptions.live', 'translation'),
                    'sandbox' => $this->dependencies->l('paymentmethods.amex.descriptions.sandbox', 'translation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.amex.link', 'translation'),
                'call_to_action' => $this->dependencies->l('paymentmethods.amex.call_to_action', 'translation'),
            ],
            'applepay' => [
                'title' => $this->dependencies->l('paymentmethods.applepay.title', 'translation'),
                'descriptions' => [
                    'live' => $this->dependencies->l('paymentmethods.applepay.descriptions.live', 'translation'),
                    'sandbox' => $this->dependencies->l('paymentmethods.applepay.descriptions.sandbox', 'translation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.applepay.link', 'translation'),
                'call_to_action' => $this->dependencies->l('paymentmethods.applepay.call_to_action', 'translation'),
            ],
            'bancontact' => [
                'title' => $this->dependencies->l('paymentmethods.bancontact.title', 'translation'),
                'descriptions' => [
                    'live' => $this->dependencies->l('paymentmethods.bancontact.descriptions.live', 'translation'),
                    'sandbox' => $this->dependencies->l('paymentmethods.bancontact.descriptions.sandbox', 'translation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.bancontact.link', 'translation'),
                'user' => [
                    'title' => $this->dependencies->l('paymentmethods.bancontact.user.title', 'translation'),
                    'description' => $this->dependencies->l('paymentmethods.bancontact.user.description', 'translation'),
                ],
                'call_to_action' => $this->dependencies->l('paymentmethods.bancontact.call_to_action', 'translation'),
            ],
            'satispay' => [
                'title' => $this->dependencies->l('paymentmethods.satispay.title', 'translation'),
                'descriptions' => [
                    'live' => $this->dependencies->l('paymentmethods.satispay.descriptions.live', 'translation'),
                    'sandbox' => $this->dependencies->l('paymentmethods.satispay.descriptions.sandbox', 'translation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.satispay.link', 'translation'),
                'call_to_action' => $this->dependencies->l('paymentmethods.satispay.call_to_action', 'translation'),
            ],
            'sofort' => [
                'title' => $this->dependencies->l('paymentmethods.sofort.title', 'translation'),
                'descriptions' => [
                    'live' => $this->dependencies->l('paymentmethods.sofort.descriptions.live', 'translation'),
                    'sandbox' => $this->dependencies->l('paymentmethods.sofort.descriptions.sandbox', 'translation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.sofort.link', 'translation'),
                'call_to_action' => $this->dependencies->l('paymentmethods.sofort.call_to_action', 'translation'),
            ],
            'giropay' => [
                'title' => $this->dependencies->l('paymentmethods.giropay.title', 'translation'),
                'descriptions' => [
                    'live' => $this->dependencies->l('paymentmethods.giropay.descriptions.live', 'translation'),
                    'sandbox' => $this->dependencies->l('paymentmethods.giropay.descriptions.sandbox', 'translation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.giropay.link', 'translation'),
                'call_to_action' => $this->dependencies->l('paymentmethods.giropay.call_to_action', 'translation'),
            ],
            'ideal' => [
                'title' => $this->dependencies->l('paymentmethods.ideal.title', 'translation'),
                'descriptions' => [
                    'live' => $this->dependencies->l('paymentmethods.ideal.descriptions.live', 'translation'),
                    'sandbox' => $this->dependencies->l('paymentmethods.ideal.descriptions.sandbox', 'translation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.ideal.link', 'translation'),
                'call_to_action' => $this->dependencies->l('paymentmethods.ideal.call_to_action', 'translation'),
            ],
            'mybank' => [
                'title' => $this->dependencies->l('paymentmethods.mybank.title', 'translation'),
                'descriptions' => [
                    'live' => $this->dependencies->l('paymentmethods.mybank.descriptions.live', 'translation'),
                    'sandbox' => $this->dependencies->l('paymentmethods.mybank.descriptions.sandbox', 'translation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.mybank.link', 'translation'),
                'call_to_action' => $this->dependencies->l('paymentmethods.mybank.call_to_action', 'translation'),
            ],
        ];
    }

    /**
     * @return array
     */
    public function getPaylaterTranslations()
    {
        return [
            'title' => $this->dependencies->l('paylater.title', 'translation'),
            'description' => $this->dependencies->l('paylater.description', 'translation'),
            'advanced' => $this->dependencies->l('paylater.advanced', 'translation'),
            'link' => $this->dependencies->l('paylater.link', 'translation'),
            'options' => [
                'title' => $this->dependencies->l('paylater.options.title', 'translation'),
                'description' => $this->dependencies->l('paylater.options.description', 'translation'),
                'with_fees' => [
                    'label' => $this->dependencies->l('paylater.options.with_fees.label', 'translation'),
                    'subtext' => $this->dependencies->l('paylater.options.with_fees.subtext', 'translation'),
                ],
                'without_fees' => [
                    'label' => $this->dependencies->l('paylater.options.without_fees.label', 'translation'),
                    'subtext' => $this->dependencies->l('paylater.options.without_fees.subtext', 'translation'),
                ],
            ],
            'oneySchedule' => [
                'title' => $this->dependencies->l('oneySchedule.title', 'translation'),
                'description' => $this->dependencies->l('oneySchedule.description', 'translation'),
                'knowMore' => [
                    'text' => $this->dependencies->l('oneySchedule.knowMore.text', 'translation'),
                ],
            ],
            'oneyPopupProduct' => [
                'title' => $this->dependencies->l('oneyPopupProduct.title', 'translation'),
            ],
            'oneyPopupCart' => [
                'title' => $this->dependencies->l('oneyPopupCart.title', 'translation'),
            ],
            'thresholds' => [
                'title' => $this->dependencies->l('thresholds.title', 'translation'),
                'description' => $this->dependencies->l('thresholds.description', 'translation'),
                'inter' => $this->dependencies->l('thresholds.inter', 'translation'),
                'error' => [
                    'default' => $this->dependencies->l('thresholds.error.text', 'translation'),
                    'max' => $this->dependencies->l('thresholds.error.max.text', 'translation'),
                    'min' => $this->dependencies->l('thresholds.error.min.text', 'translation'),
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getPaymentStatusTranslations()
    {
        return [

        ];
    }

    /**
     * @return array
     */
    public function getRequirementsTranslations()
    {
        return [
            'title' => $this->dependencies->l('payplug.getRequirementsTranslations.requirementsTitle', 'translation'),
            'descriptions' => [
                    'description' => $this->dependencies->l(
                        'payplug.getRequirementsTranslations.requirementsDescription',
                        'translation'
                    ),
                    'errorMessage' => $this->dependencies->l(
                        'payplug.getRequirementsTranslations.requirementsDescriptionErrorMessage',
                        'translation'
                    ),
                    'check' => $this->dependencies->l(
                        'payplug.getRequirementsTranslations.requirementsDescriptionsCheck',
                        'translation'
                    ),
                    'successMessage' => $this->dependencies->l(
                        'payplug.getRequirementsTranslations.requirementsDescriptionsSuccessMessage',
                        'translation'
                    ),
                ],
            'requirements' => [
                'curl' => [
                    'text' => $this->dependencies->l(
                        'payplug.getRequirementsTranslations.requirementsCurlText',
                        'translation'
                    ),
                ],
                'php' => [
                    'text' => $this->dependencies->l(
                        'payplug.getRequirementsTranslations.requirementsPhpText',
                        'translation'
                    ),
                ],
                'openssl' => [
                    'text' => $this->dependencies->l(
                        'payplug.getRequirementsTranslations.requirementsOpensslText',
                        'translation'
                    ),
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getModalTranslations()
    {
        return [
            'confirmation' => [
                'text' => $this->dependencies->l('modal.confirmation.text', 'translation'),
                'submit' => $this->dependencies->l('modal.confirmation.submit', 'translation'),
            ],
            'premium' => [
                'title' => $this->dependencies->l('modal.premium.title', 'translation'),
                'description' => [
                    'unavailable' => $this->dependencies->l('modal.premium.description.unavailable', 'translation'),
                    'form' => $this->dependencies->l('modal.premium.description.form', 'translation'),
                    'contact' => $this->dependencies->l('modal.premium.description.contact', 'translation'),
                    'default' => $this->dependencies->l('modal.premium.description.default', 'translation'),
                    'oney' => $this->dependencies->l('modal.premium.description.oney', 'translation'),
                ],
                'link' => [
                    'form' => $this->dependencies->l('modal.premium.link.form', 'translation'),
                    'contact' => $this->dependencies->l('modal.premium.link.contact', 'translation'),
                    'default' => $this->dependencies->l('modal.premium.link.default', 'translation'),
                    'oney' => $this->dependencies->l('modal.premium.link.oney', 'translation'),
                ],
                'feature' => [
                    'american_express' => $this->dependencies->l('modal.premium.feature.american_express', 'translation'),
                    'applepay' => $this->dependencies->l('modal.premium.feature.applepay', 'translation'),
                    'bancontact' => $this->dependencies->l('modal.premium.feature.bancontact', 'translation'),
                    'integrated' => $this->dependencies->l('modal.premium.feature.integrated', 'translation'),
                    'giropay' => $this->dependencies->l('modal.premium.feature.giropay', 'translation'),
                    'ideal' => $this->dependencies->l('modal.premium.feature.ideal', 'translation'),
                    'mybank' => $this->dependencies->l('modal.premium.feature.mybank', 'translation'),
                    'satispay' => $this->dependencies->l('modal.premium.feature.satispay', 'translation'),
                    'sofort' => $this->dependencies->l('modal.premium.feature.sofort', 'translation'),
                ],
                'submit' => $this->dependencies->l('modal.premium.submit', 'translation'),
            ],
        ];
    }

    /**
     * @return array
     */
    public function getFrontIntegratedPaymentTranslations()
    {
        return [
            'privacy' => $this->dependencies->l('ip.privacy', 'translation'),
            'secure' => $this->dependencies->l('ip.secure', 'translation'),
        ];
    }
}
