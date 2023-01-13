<?php
/**
 * 2013 - 2023 PayPlug SAS
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
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2023 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
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
    public function getSettingsTranslations()
    {
        return [
            'saveButton' => $this->dependencies->l('payplug.getSettingsTranslations.saveButton', 'translation'),
        ];
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
                ],
                'account' => [
                    'warning' => [
                        'title' => $this->dependencies->l('logged.inactive.account.warning.title', 'loggedtranslation'),
                        'description' => $this->dependencies->l('logged.inactive.account.warning.description', 'loggedtranslation'),
                    ],
                    'error' => [
                        'title' => $this->dependencies->l('logged.inactive.account.error.title', 'loggedtranslation'),
                        'description' => $this->dependencies->l('logged.inactive.account.error.description', 'loggedtranslation'),
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
     * @return array
     */
    public function getPaymentMethodsTranslations()
    {
        return [
            'paymentMethods' => [
                'title' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodTitle', 'translation'),
                'descriptions' => [
                    'live' => [
                        'description' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodLiveDescription', 'translation'),
                    ],
                    'test' => [
                        'description' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodTestDescription', 'translation'),
                    ],
                ],
                'standard' => [
                    'title' => [
                        'title' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodStandardTitleTitle', 'translation'),
                        'value' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodStandardTitleValue', 'translation'),
                        'descriptions' => [
                            'live' => [
                                'description' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodStandardTitleLiveDescription', 'translation'),
                                'placeholder' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodStandardTitleLivePlaceholder', 'translation'),
                            ],
                            'test' => [
                                'description' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodStandardTitleTestDescription', 'translation'),
                                'placeholder' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodStandardTitleTestPlaceholder', 'translation'),
                            ],
                        ],
                    ],
                    'description' => [
                        'title' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodStandardDescriptionTitle', 'translation'),
                        'value' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodStandardDescriptionValue', 'translation'),
                        'descriptions' => [
                            'live' => [
                                'description' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodStandardDescriptionLiveDescription', 'translation'),
                                'placeholder' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodStandardDescriptionLivePlaceholder', 'translation'),
                            ],
                            'test' => [
                                'description' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodStandardDescriptionTestDescription', 'translation'),
                                'placeholder' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodStandardDescriptionTestPlaceholder', 'translation'),
                            ],
                        ],
                    ],
                ],
                'embedded' => [
                    'title' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodEmbeddedTitle', 'translation'),
                    'descriptions' => [
                        'live' => [
                            'descriptionRedirect' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodEmbeddedLiveDescriptionRedirect', 'translation'),
                            'descriptionPopup' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodEmbeddedLiveDescriptionPopup', 'translation'),
                            'knowMore' => [
                                'text' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodEmbeddedLiveKnowMoreText', 'translation'),
                            ],
                        ],
                        'test' => [
                            'descriptionRedirect' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodEmbeddedTestDescriptionRedirect', 'translation'),
                            'descriptionPopup' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodEmbeddedTestDescriptionPopup', 'translation'),
                            'knowMore' => [
                                'text' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodEmbeddedTestKnowMoreText', 'translation'),
                            ],
                        ],
                    ],
                    'popupValue' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodEmbeddedPopupValue', 'translation'),
                    'redirectValue' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodEmbeddedRedirectValue', 'translation'),
                ],
                'oneClick' => [
                    'title' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodOneClickTitle', 'translation'),
                    'descriptions' => [
                        'live' => [
                            'description' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodOneClickLiveDescription', 'translation'),
                            'knowMore' => [
                                'text' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodOneClickLiveKnowMoreText', 'translation'),
                            ],
                        ],
                        'test' => [
                            'description' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodOneClickTestDescription', 'translation'),
                            'knowMore' => [
                                'text' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodOneClickTestKnowMoreText', 'translation'),
                            ],
                        ],
                    ],
                ],
                'americanExpress' => [
                    'title' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodAmericanExpressTitle', 'translation'),
                    'descriptions' => [
                        'live' => [
                            'description' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodAmericanExpressLiveDescription', 'translation'),
                            'knowMore' => [
                                'text' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodAmericanExpressLiveKnowMoreText', 'translation'),
                            ],
                        ],
                        'test' => [
                            'description' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodAmericanExpressTestDescription', 'translation'),
                            'knowMore' => [
                                'text' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodAmericanExpressTestKnowMoreText', 'translation'),
                            ],
                        ],
                    ],
                ],
                'applePay' => [
                    'title' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodApplePayTitle', 'translation'),
                    'descriptions' => [
                        'live' => [
                            'description' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodApplePayLiveDescription', 'translation'),
                            'knowMore' => [
                                'text' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodApplePayLiveKnowMoreText', 'translation'),
                            ],
                        ],
                        'test' => [
                            'description' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodApplePayTestDescription', 'translation'),
                            'knowMore' => [
                                'text' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodApplePayTestKnowMoreText', 'translation'),
                            ],
                        ],
                    ],
                ],
                'bancontact' => [
                    'title' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodBancontactTitle', 'translation'),
                    'descriptions' => [
                        'live' => [
                            'description' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodBancontactLiveDescription', 'translation'),
                            'knowMore' => [
                                'text' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodBancontactLiveKnowMoreText', 'translation'),
                            ],
                        ],
                        'test' => [
                            'description' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodBancontactTestDescription', 'translation'),
                            'knowMore' => [
                                'text' => $this->dependencies->l('payplug.getPaymentMethodsTranslations.paymentMethodBancontactTestKnowMoreText', 'translation'),
                            ],
                        ],
                    ],
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
            'paylater' => [
                'title' => $this->dependencies->l('payplug.getPaylaterTranslations.paylaterTitle', 'translation'),
                'descriptions' => [
                    'live' => [
                        'description' => $this->dependencies->l('payplug.getPaylaterTranslations.paylaterLiveDescription', 'translation'),
                    ],
                    'test' => [
                        'description' => $this->dependencies->l('payplug.getPaylaterTranslations.paylaterTestDescription', 'translation'),
                    ],
                ],
                'options' => [
                    'title' => $this->dependencies->l('payplug.getPaylaterTranslations.paylaterOptionsTitle', 'translation'),
                    'live' => [
                        'description' => $this->dependencies->l('payplug.getPaylaterTranslations.paylaterOptionsLiveDescription', 'translation'),
                        'knowMoreText' => $this->dependencies->l('payplug.getPaylaterTranslations.paylaterOptionsLiveKnowMoreText', 'translation'),
                    ],
                    'test' => [
                        'description' => $this->dependencies->l('payplug.getPaylaterTranslations.paylaterOptionsTestDescription', 'translation'),
                        'knowMoreText' => $this->dependencies->l('payplug.getPaylaterTranslations.paylaterOptionsTestKnowMoreText', 'translation'),
                    ],
                    'advanced' => [
                        'description' => $this->dependencies->l('payplug.getPaylaterTranslations.paylaterOptionsAdvancedDescription', 'translation'),
                    ],
                    'option1' => [
                        'label' => $this->dependencies->l('payplug.getPaylaterTranslations.paylaterOptionsOption1Label', 'translation'),
                        'subtext' => $this->dependencies->l('payplug.getPaylaterTranslations.paylaterOptionsOption1Subtext', 'translation'),
                    ],
                    'option2' => [
                        'label' => $this->dependencies->l('payplug.getPaylaterTranslations.paylaterOptionsOption2Label', 'translation'),
                        'subtext' => $this->dependencies->l('payplug.getPaylaterTranslations.paylaterOptionsOption2Subtext', 'translation'),
                    ],
                ],
                'oneyPopupProduct' => [
                    'title' => $this->dependencies->l('payplug.getPaylaterTranslations.oneyPopupProductTitle', 'translation'),
                    'description' => $this->dependencies->l('payplug.getPaylaterTranslations.oneyPopupProductDescription', 'translation'),
                    'knowMore' => [
                        'text' => $this->dependencies->l('payplug.getPaylaterTranslations.oneyPopupProductKnowMoreText', 'translation'),
                    ],
                ],
                'thresholds' => [
                    'title' => $this->dependencies->l('payplug.getPaylaterTranslations.paylaterThresholdsTitle', 'translation'),
                    'description' => $this->dependencies->l('payplug.getPaylaterTranslations.paylaterThresholdsDescription', 'translation'),
                    'inter' => $this->dependencies->l('payplug.getPaylaterTranslations.paylaterThresholdsInter', 'translation'),
                    'error' => [
                        'text' => $this->dependencies->l('payplug.getPaylaterTranslations.paylaterThresholdsTitle', 'translation'),
                    ],
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
            'requirements' => [
                'title' => $this->dependencies->l('payplug.getRequirementsTranslations.requirementsTitle', 'translation'),
                'descriptions' => [
                    'live' => [
                        'description' => $this->dependencies->l('payplug.getRequirementsTranslations.requirementsDescriptionsLiveDescription', 'translation'),
                        'errorMessage' => $this->dependencies->l('payplug.getRequirementsTranslations.requirementsDescriptionsLiveErrorMessage', 'translation'),
                        'check' => $this->dependencies->l('payplug.getRequirementsTranslations.requirementsDescriptionsLiveCheck', 'translation'),
                    ],
                    'test' => [
                        'description' => $this->dependencies->l('payplug.getRequirementsTranslations.requirementsDescriptionsTestDescription', 'translation'),
                        'errorMessage' => $this->dependencies->l('payplug.getRequirementsTranslations.requirementsDescriptionsTestErrorMessage', 'translation'),
                        'check' => $this->dependencies->l('payplug.getRequirementsTranslations.requirementsDescriptionsTestCheck', 'translation'),
                    ],
                ],
                'requirements' => [
                    'curl' => [
                        'text' => $this->dependencies->l('payplug.getRequirementsTranslations.requirementsRequirementsCurlText', 'translation'),
                    ],
                    'php' => [
                        'text' => $this->dependencies->l('payplug.getRequirementsTranslations.requirementsRequirementsPhpText', 'translation'),
                    ],
                    'openssl' => [
                        'text' => $this->dependencies->l('payplug.getRequirementsTranslations.requirementsRequirementsOpensslText', 'translation'),
                    ],
                    'currency' => [
                        'text' => $this->dependencies->l('payplug.getRequirementsTranslations.requirementsRequirementsCurrencyText', 'translation'),
                    ],
                    'account' => [
                        'text' => $this->dependencies->l('payplug.getRequirementsTranslations.requirementsRequirementsAccountText', 'translation'),
                    ],
                ],
                'debug' => [
                    'live' => [
                        'title' => $this->dependencies->l('payplug.getRequirementsTranslations.requirementsDebugLiveTitle', 'translation'),
                        'description' => $this->dependencies->l('payplug.getRequirementsTranslations.requirementsDebugLiveDescription', 'translation'),
                    ],
                    'test' => [
                        'title' => $this->dependencies->l('payplug.getRequirementsTranslations.requirementsDebugTestTitle', 'translation'),
                        'description' => $this->dependencies->l('payplug.getRequirementsTranslations.requirementsDebugTestDescription', 'translation'),
                    ],
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
            'modal' => [
                'confirmation' => [
                    'text' => $this->dependencies->l('payplug.getModalTranslations.modalConfirmationText', 'translation'),
                    'submit' => $this->dependencies->l('payplug.getModalTranslations.modalConfirmationSubmit', 'translation'),
                ],
                'error' => [
                    'submit' => $this->dependencies->l('payplug.getModalTranslations.modalErrorSubmit', 'translation'),
                ],
                'premium' => [
                    'feature' => [
                        'unavailable' => $this->dependencies->l('payplug.getModalTranslations.modalPremiumFeatureUnavailable', 'translation'),
                        'activateOney' => $this->dependencies->l('payplug.getModalTranslations.modalPremiumFeatureActivateOney', 'translation'),
                        'activateBancontact' => $this->dependencies->l('payplug.getModalTranslations.modalPremiumFeatureActivateBancontact', 'translation'),
                        'activateApplePay' => $this->dependencies->l('payplug.getModalTranslations.modalPremiumFeatureActivateApplePay', 'translation'),
                        'activateAmex' => $this->dependencies->l('payplug.getModalTranslations.modalPremiumFeatureActivateAmex', 'translation'),
                        'activate' => $this->dependencies->l('payplug.getModalTranslations.modalPremiumFeatureActivate', 'translation'),
                    ],
                    'PremiumOk' => $this->dependencies->l('payplug.getModalTranslations.modalPremiumOk', 'translation'),
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getAlertTranslations()
    {
        return [
            'orderState' => $this->dependencies->l('payplug.getAlertTranslations.orderState', 'translation'),
        ];
    }
}
