<?php

namespace PayPlug\src\specific;

//use Media;
//use PayPlug\classes\PayPlugBackward;

class PrestashopSpecific16
{
    public function hookHeader()
    {
        die('Je suis dans le hookHeader de la classe PrestashopSpecific16');
//        Media::addJsDef(array(
//            'payplug_ajax_url' => PayplugBackward::getModuleLink($this->name, 'ajax', array(), true),
//        ));
//        $this->assignOneyJSVar();
    }

    public function hookCustomerAccount()
    {
//        $payplug_icon_url = 'modules/payplug/views/img/logo26.png';
//
//        $this->smarty->assign(array(
//            'payplug_icon_url' => $payplug_icon_url
//        ));
    }
}