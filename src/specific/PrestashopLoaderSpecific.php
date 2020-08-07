<?php

// Here we want to load specific files like src/specific/PrestashopSpecific16.php
$prestaVersion = str_replace('.','',substr(_PS_VERSION_,0,3));
$specificPresta = _PS_MODULE_DIR_ . 'payplug/src/specific/PrestashopSpecific'.$prestaVersion.'.php';

if (is_file($specificPresta))
{
    require_once($specificPresta);
}