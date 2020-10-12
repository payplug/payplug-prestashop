<?php

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