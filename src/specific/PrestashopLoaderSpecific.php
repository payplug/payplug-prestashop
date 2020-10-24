<?php
$PSfile = _PS_MODULE_DIR_ . 'payplug/src/specific/PrestashopSpecific'._PS_VERSION_[0]._PS_VERSION_[2].'.php';

// PS 1.7 n'aime pas 'include_once' si le fichier n'existe pas : Erreur pdt install du module
if (is_file($PSfile)) {
    require_once ($PSfile);
}