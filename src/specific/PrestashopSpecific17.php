<?php
/**
 * 2013 - 2020 PayPlug SAS
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
 * @copyright 2013 - 2020 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\specific;

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class PrestashopSpecific17
{
    public $payplug;

    public function __construct($payplug)
    {
        $this->payplug = $payplug;
    }

    public function hookHeader()
    {
        $this->payplug->addCSSRC(__PS_BASE_URI__ . 'modules/payplug/views/css/front.css');
        $this->payplug->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/front.js');
    }

    public function displayPaymentOption($payment_options)
    {
        foreach($payment_options as $payment_option)
        {
            /*
             * 1è condition : Si OneClick activé mais pas de carte enregistré, on sort
             * 2è condition : Si, dans la boucle, c'est au tour de 'payplug_cards', on sort pour pas instancier PaymentOption()
             */

            $paymentOption = new PaymentOption();
            if (isset($payment_option['expiry_date_card'])) {
                $payment_option['callToActionText'] = $payment_option['callToActionText'] .' - '. $payment_option['expiry_date_card'];
            }
            $paymentOption
                ->setLogo($payment_option['logo'])
                ->setCallToActionText($payment_option['callToActionText'])
                ->setAction($payment_option['action'])
                ->setModuleName($payment_option['moduleName'])
                ->setInputs($payment_option['inputs']);

            if (isset($payment_option['additionalInformation']))
            {
                $paymentOption->setAdditionalInformation($payment_option['additionalInformation']); // Échéanciers Oney
            }

            $paymentOptions[] = $paymentOption;
        }

        return $paymentOptions;
    }

}
