<?php

$available_languages = ['fr','en','gb','it'];

$headerContent = '<?php
global $_MODULE;
$_MODULE = array();
';


foreach ($available_languages as $keyLang => $lang) {
    var_dump($lang);
    $writeFile = fopen( dirname(__FILE__) . '/../translations/' . $lang . '.php', 'w');
    fwrite( $writeFile , $headerContent);

    $count = 0;
    $readFile = fopen('translations.csv', 'r');
    while (($line = fgetcsv($readFile)) !== false) {
        //$line is an array of the csv elements
        if ($count && ($line[$keyLang + 2] != '') ) {

            fwrite( $writeFile, '$_MODULE[\'<{payplug}prestashop>' . $line[0] . '\'] = \'' . addslashes($line[$keyLang + 2]) . '\';' . PHP_EOL);

        }
        $count++;
      }
    $count = 0;
    fclose($readFile);
    fclose($writeFile);

}