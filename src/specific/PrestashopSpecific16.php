<?php

namespace PayPlug\src\specific;

class PrestashopSpecific16
{
    public function hookCustomerAccount()
    {
        die('ok on est dans PrestashopSpecific16, méthode hookCustomerAccount');

        $payplug_icon_url = PayplugBackward::getHttpHost(true) . __PS_BASE_URI__
            . 'modules/' . $this->name . '/views/img/logo26.png';

        $this->smarty->assign(array(
            'payplug_icon_url' => $payplug_icon_url
        ));
    }
}