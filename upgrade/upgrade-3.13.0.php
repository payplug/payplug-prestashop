<?php

function upgrade_module_3_13_0()
{
    $flag = true;

    // add  PAYPLUG_ENABLE to database
    $flag = $flag && Configuration::updateValue(
        'PAYPLUG_ENABLE',
        0
    );

    $embedded_mode = Configuration::get('PAYPLUG_EMBEDDED_MODE');
    if ('redirected' == $embedded_mode) {
        $flag = $flag && Configuration::updateValue(
            'PAYPLUG_EMBEDDED_MODE',
            'redirect'
        );
    }

    return $flag;
}
