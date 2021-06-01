<?php

$available_languages = ['fr','en','gb','it'];

$headerContent = '<?php
global $_MODULE;
$_MODULE = array();
';


foreach ($available_languages as $keyLang => $lang) {
    $writeFile = fopen(dirname(__FILE__) . '/../translations/' . $lang . '.php', 'w');
    fwrite($writeFile, $headerContent);

    $count = 0;
    $readFile = fopen(dirname(__FILE__) . '/translations.csv', 'r');
    while (($line = fgetcsv($readFile, 1000, ';')) !== false) {
        //$line is an array of the csv elements
        $key = $keyLang + 2;
        if (!array_key_exists($key, $line)) {
            echo "Erreur sur cette traduction : ";
            print_r($line);
        } else {
            if ($count && ($line[$keyLang + 2] != '')) {
                fwrite($writeFile, '$_MODULE[\'<{payplug}prestashop>' . $line[0] . '\'] = \'' . addslashes($line[$keyLang + 2]) . '\';' . PHP_EOL);
            }
            $count++;
        }
    }
    $count = 0;
    fclose($readFile);
    fclose($writeFile);
}
