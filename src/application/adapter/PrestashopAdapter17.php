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
use Symfony\Component\Dotenv\Dotenv;

class PrestashopAdapter17
{
    /**
     * @description Card brands accepted for UHF hosted-fields tokenization. Shared
     *              single source of truth for both the client-side rejection (via
     *              window['payplug_hosted_fields_accepted_brands'], see
     *              setHostedFieldsPaymentOption()) and the server-side re-validation
     *              in controllers/front/unified.php?action=create.
     */
    const HOSTED_FIELDS_ACCEPTED_BRANDS = ['cb', 'visa', 'mastercard'];

    public $payplug;
    private $configuration;
    private $constant;
    private $context;
    private $dependencies;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
        $this->configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
    }

    public function displayHeader()
    {
        $views_path = $this->constant->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/';
        $this->context->controller->addCSS($views_path . '/css/front-' . $this->dependencies->version . '.css');
        $this->context->controller->addJS($views_path . '/js/utilities-' . $this->dependencies->version . '.js');
        $this->context->controller->addJS($views_path . '/js/front-' . $this->dependencies->version . '.js');
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
        // 'integrated' is EUR-only; a non-EUR cart routes to 'hosted_fields' below
        // instead (PRE-3623) - the currency check here is deliberate, not redundant.
        if ($this->dependencies->configClass->isValidFeature('feature_standard')
            && $this->dependencies->configClass->isValidFeature('feature_integrated')
            && array_key_exists('standard', $payment_options)
            && 'integrated' == (string) $this->configuration->getvalue('embedded_mode')
            && 'EUR' === $this->context->currency->iso_code
        ) {
            $payment_options = $this->setIntegratedPaymentOption($payment_options);
        } elseif ($this->dependencies->configClass->isValidFeature('feature_standard')
            && $this->dependencies->configClass->isValidFeature('feature_hosted_fields')
            && array_key_exists('standard', $payment_options)
            && 'EUR' !== $this->context->currency->iso_code
            && $this->isHostedFieldsIdentifierConfigured()
        ) {
            $payment_options = $this->setHostedFieldsPaymentOption($payment_options);
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

            // load the official Oney widget checkout section (legal requirement)
            if ('oney' == $payment_method && $payment_option['is_optimized']) {
                try {
                    $checkout_section = $this->dependencies
                        ->getPlugin()
                        ->getOneyAction()
                        ->renderCheckoutSection(
                            $payment_option['type'],
                            $payment_option['amount'],
                            $payment_option['iso_code']
                        );
                } catch (\Exception $e) {
                    // todo: set a permanent log
                    $checkout_section = false;
                }

                if ($checkout_section) {
                    $payment_option['additionalInformation'] = $checkout_section;
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
     * @description  since PRE-3623, only reached for EUR carts (see displayPaymentOption())
     *
     * @param $payment_options
     *
     * @return mixed
     */
    public function setIntegratedPaymentOption($payment_options)
    {
        if (empty($payment_options)) {
            return [];
        }
        $dotenv = new Dotenv();
        $dotenvFile = dirname(__FILE__, 5) . '/payplugroutes/.env';
        if (file_exists($dotenvFile)) {
            $dotenv->load($dotenvFile);
            $integrated_payment_js_url = $_ENV['INTEGRATED_PAYMENT_DOMAIN'];
        } else {
            $integrated_payment_js_url = $this->dependencies
                ->getPlugin()
                ->getRoutes()
                ->getSourceUrl()['integrated'];
        }
        $integrated = [];
        $integrated['name'] = 'integrated';
        $integrated['inputs']['method'] = [
            'name' => 'method',
            'type' => 'hidden',
            'value' => 'integrated',
        ];
        $integrated['action'] = 'javascript:payplugModule.integrated.form.validate();';
        $integrated['logo'] = $payment_options['standard']['logo'];
        $integrated['moduleName'] = 'payplug';
        $integrated['callToActionText'] = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->l('specific17.setIntegratedPaymentOption.name', 'prestashopadapter17');
        $integrated['tpl'] = 'integrated_payment.tpl';
        $integrated['extra_classes'] = 'payplug integrated';

        $translation = $this->dependencies->getPlugin()->getTranslationClass()->getFrontIntegratedPaymentTranslations();

        $privacyLink = $this->getPrivacyLink();

        $payment_methods = json_decode($this->dependencies->getPlugin()->getConfigurationClass()->getValue('payment_methods'), true);

        $this->context->smarty->assign(array_merge(
            [
                'integrated_payment_js_url' => $integrated_payment_js_url,
                'is_one_click_activated' => (bool) $payment_methods['one_click'],
                'is_deferred_activated' => (bool) $payment_methods['deferred'],
                'privacy' => $translation['privacy'],
                'secure' => $translation['secure'],
                'privacyLink' => $privacyLink,
            ],
            $this->getEmbeddedPlaceholderTranslations()
        ));

        $integrated['additionalInformation'] =
            $this->dependencies->configClass->fetchTemplate('checkout/payment/integrated_payment.tpl');

        $payment_options['standard'] = $integrated;

        return $payment_options;
    }

    /**
     * @description Whether the shop has a UHF identifier configured for the given
     *              currency (PRE-3622, Configuration::hosted_fields, JSON map of
     *              lowercase ISO code => identifier). Defaults to the request
     *              context's currency when $isoCode is not passed, so existing
     *              call sites (e.g. displayPaymentOption()) are unaffected.
     *
     * @param string|null $isoCode
     *
     * @return bool
     */
    public function isHostedFieldsIdentifierConfigured($isoCode = null)
    {
        return '' !== $this->getHostedFieldsIdentifier($isoCode);
    }

    /**
     * @description UHF identifier configured for the given currency, used to
     *              initialize the client-side hosted-fields widget. Defaults to
     *              the request context's currency when $isoCode is not passed.
     *              Empty string when nothing is configured for this currency,
     *              or when the configured value isn't a plain string (e.g. a
     *              malformed config entry written outside the module's own
     *              save flow) - never bypassed by casting a non-scalar to string.
     *
     * @param string|null $isoCode
     *
     * @return string
     */
    public function getHostedFieldsIdentifier($isoCode = null)
    {
        $iso_code = strtolower(null === $isoCode ? $this->context->currency->iso_code : $isoCode);
        $hosted_fields = json_decode($this->configuration->getValue('hosted_fields') ?: '{}', true);

        return isset($hosted_fields[$iso_code]) && is_string($hosted_fields[$iso_code])
            ? $hosted_fields[$iso_code]
            : '';
    }

    /**
     * @description  creation payment option
     * for hosted fields (UHF) payment, mirroring setIntegratedPaymentOption()
     *
     * @param $payment_options
     *
     * @return mixed
     */
    public function setHostedFieldsPaymentOption($payment_options)
    {
        if (empty($payment_options)) {
            return [];
        }

        $hosted_fields = [];
        // Mirrors setIntegratedPaymentOption()'s own inner 'name' => 'integrated':
        // this inner field intentionally differs from the outer array key
        // ('standard', set at the end of this method) - same divergence that
        // already exists in the code being mirrored, not a new inconsistency.
        $hosted_fields['name'] = 'hosted_fields';
        $hosted_fields['inputs']['method'] = [
            'name' => 'method',
            'type' => 'hidden',
            'value' => 'hosted_fields',
        ];
        $hosted_fields['inputs']['id_cart'] = $payment_options['standard']['inputs']['id_cart'];
        $hosted_fields['action'] = 'javascript:payplugModule.hosted_fields.form.validate();';
        $hosted_fields['logo'] = $payment_options['standard']['logo'];
        $hosted_fields['moduleName'] = 'payplug';
        $hosted_fields['callToActionText'] = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->l('specific17.setHostedFieldsPaymentOption.name', 'prestashopadapter17');
        $hosted_fields['tpl'] = 'hosted_fields.tpl';
        $hosted_fields['extra_classes'] = 'payplug hosted_fields';
        $company_id = $this->dependencies
            ->getPlugin()
            ->getConfigurationClass()
            ->getValue('oauth_company_id');

        $payment_methods = json_decode($this->dependencies->getPlugin()->getConfigurationClass()->getValue('payment_methods'), true);
        $translation = $this->dependencies->getPlugin()->getTranslationClass()->getFrontIntegratedPaymentTranslations();
        $privacyLink = $this->getPrivacyLink();

        $this->context->smarty->assign(array_merge(
            [
                'is_one_click_activated' => (bool) $payment_methods['one_click'],
                'privacy' => $translation['privacy'],
                'secure' => $translation['secure'],
                'privacyLink' => $privacyLink,

                'hosted_fields_js_url' => $this->dependencies
                    ->getPlugin()
                    ->getRoutes()
                    ->getSourceUrl()['hosted_fields'],
                'hosted_company_id' => $company_id,
                'hosted_fields_identifier' => $this->getHostedFieldsIdentifier(),
                'hosted_fields_accepted_brands' => json_encode(self::HOSTED_FIELDS_ACCEPTED_BRANDS),
                'hosted_fields_uhf_url' => $this->context->link->getModuleLink(
                    $this->dependencies->name,
                    'unified',
                    ['action' => 'create'],
                    true
                ),
            ],
            $this->getEmbeddedPlaceholderTranslations()
        ));

        $hosted_fields['additionalInformation'] =
            $this->dependencies->configClass->fetchTemplate('checkout/payment/hosted_fields.tpl');

        $payment_options['standard'] = $hosted_fields;

        return $payment_options;
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

    /**
     * @description Privacy-policy link for the current front-office language,
     *              shared by both the integrated and hosted-fields embedded
     *              checkout renderings.
     *
     * @return string
     */
    private function getPrivacyLink()
    {
        switch ($this->context->language->iso_code) {
            case 'fr':
                return 'https://www.payplug.com/fr/politique-de-confidentialite/';

            case 'it':
                return 'https://www.payplug.com/it/politica-di-confidenzialita/';

            default:
                return 'https://www.payplug.com/privacy-policy/';
        }
    }

    /**
     * @description The four hosted-card-field placeholder translations, shared
     *              by both the integrated and hosted-fields embedded checkout
     *              renderings (both use the exact same 'specific17.setIntegratedPaymentOption.placeholder*'
     *              keys - the hosted-fields rendering deliberately reuses these
     *              rather than defining its own, since the placeholder text is
     *              identical).
     *
     * @return array
     */
    private function getEmbeddedPlaceholderTranslations()
    {
        return [
            'placeholderCardholder' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('specific17.setIntegratedPaymentOption.placeholderCardholder', 'prestashopadapter17'),
            'placeholderPan' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('specific17.setIntegratedPaymentOption.placeholderPan', 'prestashopadapter17'),
            'placeholderExp' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('specific17.setIntegratedPaymentOption.placeholderExp', 'prestashopadapter17'),
            'placeholderCvv' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('specific17.setIntegratedPaymentOption.placeholderCvv', 'prestashopadapter17'),
        ];
    }
}
