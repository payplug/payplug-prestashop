<?php
/**
 * 2013 - 2023 Payplug SAS
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
 * @copyright 2013 - 2023 Payplug SAS
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
                'text' => $this->dependencies->l('footer.button.text', 'footertranslation'),
            ],
            'faq' => [
                'top' => $this->dependencies->l('footer.faq.top', 'footertranslation'),
                'bottom' => $this->dependencies->l('footer.faq.bottom', 'footertranslation'),
                'link' => $this->dependencies->l('footer.faq.link', 'footertranslation'),
            ],
        ];
    }

    /**
     * @return array
     */
    public function getHeaderTranslations()
    {
        return [
            'hidden' => $this->dependencies->l('payplug.getHeaderTranslations.headerHidden', 'headertranslation'),
            'visible' => $this->dependencies->l('payplug.getHeaderTranslations.headerVisible', 'headertranslation'),
            'title' => $this->dependencies->l('payplug.getHeaderTranslations.headerTitle', 'headertranslation'),
            'text' => $this->dependencies->l('payplug.getHeaderTranslations.headerText', 'headertranslation'),
        ];
    }

    /**
     * @return array
     */
    public function getLoginTranslations()
    {
        return [
            'title' => $this->dependencies->l('login.title', 'logintranslation'),
            'description' => $this->dependencies->l('login.description', 'logintranslation'),
            'email' => $this->dependencies->l('login.email', 'logintranslation'),
            'password' => $this->dependencies->l('login.password', 'logintranslation'),
            'register' => $this->dependencies->l('login.register', 'logintranslation'),
            'connect' => $this->dependencies->l('login.connect', 'logintranslation'),
            'forgot_password' => $this->dependencies->l('login.forgot_password', 'logintranslation'),
            'login_error' => $this->dependencies->l('login.error', 'logintranslation'),
        ];
    }

    /**
     * @return array
     */
    public function getLoggedTranslations()
    {
        return [
            'title' => $this->dependencies->l('logged.title', 'loggedtranslation'),
            'description' => $this->dependencies->l('logged.description', 'loggedtranslation'),
            'user' => [
                'link' => $this->dependencies->l('logged.user.link', 'loggedtranslation'),
                'logout' => $this->dependencies->l('logged.user.logout', 'loggedtranslation'),
            ],
            'mode' => [
                'title' => $this->dependencies->l('logged.mode.title', 'loggedtranslation'),
                'description' => [
                    'live' => $this->dependencies->l('logged.mode.description.live', 'loggedtranslation'),
                    'sandbox' => $this->dependencies->l('logged.mode.description.sandbox', 'loggedtranslation'),
                ],
                'link' => [
                    'live' => $this->dependencies->l('logged.mode.link.live', 'loggedtranslation'),
                    'sandbox' => $this->dependencies->l('logged.mode.link.sandbox', 'loggedtranslation'),
                ],
                'options' => [
                    'live' => $this->dependencies->l('logged.mode.options.live', 'loggedtranslation'),
                    'sandbox' => $this->dependencies->l('logged.mode.options.sandbox', 'loggedtranslation'),
                ],
            ],
            'inactive' => [
                'modal' => [
                    'title' => $this->dependencies->l('logged.inactive.modal.title', 'loggedtranslation'),
                    'description' => $this->dependencies->l('logged.inactive.modal.description', 'loggedtranslation'),
                    'password_label' => $this->dependencies->l('logged.inactive.modal.password_label', 'loggedtranslation'),
                    'cancel' => $this->dependencies->l('logged.inactive.modal.cancel', 'loggedtranslation'),
                    'ok' => $this->dependencies->l('logged.inactive.modal.ok', 'loggedtranslation'),
                    'error' => $this->dependencies->l('logged.inactive.modal.error', 'loggedtranslation'),
                ],
                'account' => [
                    'warning' => [
                        'title' => $this->dependencies->l('logged.inactive.account.warning.title', 'loggedtranslation'),
                        'description' => $this->dependencies->l('logged.inactive.account.warning.description', 'loggedtranslation'),
                        'link' => $this->dependencies->l('logged.inactive.account.warning.link', 'loggedtranslation'),
                        'trigger' => $this->dependencies->l('logged.inactive.account.warning.trigger', 'loggedtranslation'),
                    ],
                    'error' => [
                        'title' => $this->dependencies->l('logged.inactive.account.error.title', 'loggedtranslation'),
                        'description' => $this->dependencies->l('logged.inactive.account.error.description', 'loggedtranslation'),
                    ],
                    'success' => [
                        'title' => $this->dependencies->l('logged.inactive.account.success.title', 'loggedtranslation'),
                        'description' => $this->dependencies->l('logged.inactive.account.success.description', 'loggedtranslation'),
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
            'title' => $this->dependencies->l('subscribe.title', 'subscribetranslation'),
            'description' => $this->dependencies->l('subscribe.description', 'subscribetranslation'),
            'text' => $this->dependencies->l('subscribe.text', 'subscribetranslation'),
            'register' => $this->dependencies->l('subscribe.register', 'subscribetranslation'),
            'connect' => $this->dependencies->l('subscribe.connect', 'subscribetranslation'),
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
            'title' => $this->dependencies->l('paymentmethods.title', 'paymentmethodstranslation'),
            'description' => $this->dependencies->l('paymentmethods.description', 'paymentmethodstranslation'),
            'standard' => [
                'title' => $this->dependencies->l('paymentmethods.standard.title', 'paymentmethodstranslation'),
                'descriptions' => [
                    'live' => $this->dependencies->l('paymentmethods.standard.descriptions.live', 'paymentmethodstranslation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.standard.link', 'paymentmethodstranslation'),
                'advanced' => $this->dependencies->l('paymentmethods.standard.advanced', 'paymentmethodstranslation'),
            ],
            'embedded' => [
                'title' => $this->dependencies->l('paymentmethods.embedded.title', 'paymentmethodstranslation'),
                'descriptions' => [
                    'popup' => [
                        'text' => $this->dependencies->l('paymentmethods.embedded.descriptions.popup.text', 'paymentmethodstranslation'),
                        'link' => $this->dependencies->l('paymentmethods.embedded.descriptions.popup.link', 'paymentmethodstranslation'),
                    ],
                    'redirect' => [
                        'text' => $this->dependencies->l('paymentmethods.embedded.descriptions.redirect.text', 'paymentmethodstranslation'),
                        'link' => $this->dependencies->l('paymentmethods.embedded.descriptions.redirect.link', 'paymentmethodstranslation'),
                    ],
                    'integrated' => $this->dependencies->l('paymentmethods.embedded.descriptions.integrated', 'paymentmethodstranslation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.embedded.link', 'paymentmethodstranslation'),
                'options' => [
                    'integrated' => $this->dependencies->l('paymentmethods.embedded.options.integrated', 'paymentmethodstranslation'),
                    'popup' => $this->dependencies->l('paymentmethods.embedded.options.popup', 'paymentmethodstranslation'),
                    'redirect' => $this->dependencies->l('paymentmethods.embedded.options.redirect', 'paymentmethodstranslation'),
                ],
            ],
            'one_click' => [
                'title' => $this->dependencies->l('paymentmethods.one_click.title', 'paymentmethodstranslation'),
                'descriptions' => [
                    'live' => $this->dependencies->l('paymentmethods.one_click.descriptions.live', 'paymentmethodstranslation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.one_click.link', 'paymentmethodstranslation'),
            ],
            'installment' => [
                'title' => $this->dependencies->l('paymentmethods.installment.title', 'paymentmethodstranslation'),
                'descriptions' => [
                    'description_1' => $this->dependencies->l('paymentmethods.installment.descriptions.description_1', 'paymentmethodstranslation'),
                    'text_from' => $this->dependencies->l('paymentmethods.installment.descriptions.text_from', 'paymentmethodstranslation'),
                    'description_2' => $this->dependencies->l('paymentmethods.installment.descriptions.description_2', 'paymentmethodstranslation'),
                    'controller_link' => $this->dependencies->l('paymentmethods.installment.descriptions.controller_link', 'paymentmethodstranslation'),
                    'alert' => [
                        'start' => $this->dependencies->l('paymentmethods.installment.descriptions.alert.start', 'paymentmethodstranslation'),
                        'end' => $this->dependencies->l('paymentmethods.installment.descriptions.alert.end', 'paymentmethodstranslation'),
                    ],
                ],
                'select' => [
                    '2_schedules' => $this->dependencies->l('paymentmethods.installment.select.2_schedules', 'paymentmethodstranslation'),
                    '3_schedules' => $this->dependencies->l('paymentmethods.installment.select.3_schedules', 'paymentmethodstranslation'),
                    '4_schedules' => $this->dependencies->l('paymentmethods.installment.select.4_schedules', 'paymentmethodstranslation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.installment.link', 'paymentmethodstranslation'),
                'error_limit' => $this->dependencies->l('paymentmethods.installment.error_limit', 'paymentmethodstranslation'),
            ],
            'deferred' => [
                'title' => $this->dependencies->l('paymentmethods.deferred.title', 'paymentmethodstranslation'),
                'descriptions' => [
                    'description_1' => $this->dependencies->l('paymentmethods.deferred.descriptions.description_1', 'paymentmethodstranslation'),
                    'description_2' => $this->dependencies->l('paymentmethods.deferred.descriptions.description_2', 'paymentmethodstranslation'),
                ],
                'states' => [
                    'default' => $this->dependencies->l('paymentmethods.deferred.states.default', 'paymentmethodstranslation'),
                    'state' => $this->dependencies->l('paymentmethods.deferred.states.state', 'paymentmethodstranslation'),
                    'alert' => $this->dependencies->l('paymentmethods.deferred.states.alert', 'paymentmethodstranslation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.deferred.link', 'paymentmethodstranslation'),
            ],
            'amex' => [
                'title' => $this->dependencies->l('paymentmethods.amex.title', 'paymentmethodstranslation'),
                'descriptions' => [
                    'live' => $this->dependencies->l('paymentmethods.amex.descriptions.live', 'paymentmethodstranslation'),
                    'sandbox' => $this->dependencies->l('paymentmethods.amex.descriptions.sandbox', 'paymentmethodstranslation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.amex.link', 'paymentmethodstranslation'),
            ],
            'applepay' => [
                'title' => $this->dependencies->l('paymentmethods.applepay.title', 'paymentmethodstranslation'),
                'descriptions' => [
                    'live' => $this->dependencies->l('paymentmethods.applepay.descriptions.live', 'paymentmethodstranslation'),
                    'sandbox' => $this->dependencies->l('paymentmethods.applepay.descriptions.sandbox', 'paymentmethodstranslation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.applepay.link', 'paymentmethodstranslation'),
            ],
            'bancontact' => [
                'title' => $this->dependencies->l('paymentmethods.bancontact.title', 'paymentmethodstranslation'),
                'descriptions' => [
                    'live' => $this->dependencies->l('paymentmethods.bancontact.descriptions.live', 'paymentmethodstranslation'),
                    'sandbox' => $this->dependencies->l('paymentmethods.bancontact.descriptions.sandbox', 'paymentmethodstranslation'),
                ],
                'link' => $this->dependencies->l('paymentmethods.bancontact.link', 'paymentmethodstranslation'),
                'user' => [
                    'title' => $this->dependencies->l('paymentmethods.bancontact.user.title', 'paymentmethodstranslation'),
                    'description' => $this->dependencies->l('paymentmethods.bancontact.user.description', 'paymentmethodstranslation'),
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getPaylaterTranslations()
    {
        return [
            'title' => $this->dependencies->l('paylater.title', 'paylatertranslation'),
            'description' => $this->dependencies->l('paylater.description', 'paylatertranslation'),
            'advanced' => $this->dependencies->l('paylater.advanced', 'paylatertranslation'),
            'link' => $this->dependencies->l('paylater.link', 'paylatertranslation'),
            'options' => [
                'title' => $this->dependencies->l('paylater.options.title', 'paylatertranslation'),
                'description' => $this->dependencies->l('paylater.options.description', 'paylatertranslation'),
                'with_fees' => [
                    'label' => $this->dependencies->l('paylater.options.with_fees.label', 'paylatertranslation'),
                    'subtext' => $this->dependencies->l('paylater.options.with_fees.subtext', 'paylatertranslation'),
                ],
                'without_fees' => [
                    'label' => $this->dependencies->l('paylater.options.without_fees.label', 'paylatertranslation'),
                    'subtext' => $this->dependencies->l('paylater.options.without_fees.subtext', 'paylatertranslation'),
                ],
            ],
            'oneySchedule' => [
                'title' => $this->dependencies->l('oneySchedule.title', 'paylatertranslation'),
                'description' => $this->dependencies->l('oneySchedule.description', 'paylatertranslation'),
                'knowMore' => [
                    'text' => $this->dependencies->l('oneySchedule.knowMore.text', 'paylatertranslation'),
                ],
            ],
            'oneyPopupProduct' => [
                'title' => $this->dependencies->l('oneyPopupProduct.title', 'paylatertranslation'),
            ],
            'oneyPopupCart' => [
                'title' => $this->dependencies->l('oneyPopupCart.title', 'paylatertranslation'),
            ],
            'thresholds' => [
                'title' => $this->dependencies->l('thresholds.title', 'paylatertranslation'),
                'description' => $this->dependencies->l('thresholds.description', 'paylatertranslation'),
                'inter' => $this->dependencies->l('thresholds.inter', 'paylatertranslation'),
                'error' => [
                    'text' => $this->dependencies->l('thresholds.error.text', 'paylatertranslation'),
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
                'text' => $this->dependencies->l('modal.confirmation.text', 'modaltranslation'),
                'submit' => $this->dependencies->l('modal.confirmation.submit', 'modaltranslation'),
            ],
            'premium' => [
                'title' => $this->dependencies->l('modal.premium.title', 'modaltranslation'),
                'description' => [
                    'unavailable' => $this->dependencies->l('modal.premium.description.unavailable', 'modaltranslation'),
                    'form' => $this->dependencies->l('modal.premium.description.form', 'modaltranslation'),
                    'contact' => $this->dependencies->l('modal.premium.description.contact', 'modaltranslation'),
                    'default' => $this->dependencies->l('modal.premium.description.default', 'modaltranslation'),
                    'oney' => $this->dependencies->l('modal.premium.description.oney', 'modaltranslation'),
                ],
                'link' => [
                    'form' => $this->dependencies->l('modal.premium.link.form', 'modaltranslation'),
                    'contact' => $this->dependencies->l('modal.premium.link.contact', 'modaltranslation'),
                    'default' => $this->dependencies->l('modal.premium.link.default', 'modaltranslation'),
                    'oney' => $this->dependencies->l('modal.premium.link.oney', 'modaltranslation'),
                ],
                'feature' => [
                    'bancontact' => $this->dependencies->l('modal.premium.feature.bancontact', 'modaltranslation'),
                    'applepay' => $this->dependencies->l('modal.premium.feature.applepay', 'modaltranslation'),
                    'american_express' => $this->dependencies->l('modal.premium.feature.american_express', 'modaltranslation'),
                ],
                'submit' => $this->dependencies->l('modal.premium.submit', 'modaltranslation'),
            ],
        ];
    }
}
