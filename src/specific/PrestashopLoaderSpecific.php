<?php
$specificPresta = _PS_MODULE_DIR_ . 'payplug/src/specific/PrestashopSpecific'._PS_VERSION_[0]._PS_VERSION_[2].'.php';
if (is_file($specificPresta))
{
    require_once($specificPresta);
}