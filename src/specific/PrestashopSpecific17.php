<?php

namespace PayPlug\src\specific;

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class PrestashopSpecific17
{
    public function displayPaymentOption($payment_options)
    {
        foreach($payment_options as $payment_option)
        {
            /*
             * 1è condition : Si OneClick activé mais pas de carte enregistré, on sort
             * 2è condition : Si, dans la boucle, c'est au tour de 'payplug_cards', on sort pour pas instancier PaymentOption()
             */
            if ((isset($payment_option['name'])
                && $payment_option['name'] == 'one_click'
                && empty($payment_options['payplug_cards']))
            ||
                (isset($payment_option['name'])
                &&
                ($payment_option['name'] == 'payplug_cards'))) {

                continue;
            }

            $paymentOption = new PaymentOption();
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