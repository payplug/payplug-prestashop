<?php

function upgrade_module_3_13_0()
{
    $flag = true;

    // add  PAYPLUG_APPLEPAY to database
    return $flag && Configuration::updateValue(
        'PAYPLUG_ENABLE',
        0
    );
}
