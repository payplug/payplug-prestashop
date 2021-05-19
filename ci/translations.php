<?php
require_once(dirname(__FILE__) . '/../vendor/autoload.php');

$repo = new PayPlug\src\repositories\TranslationsRepository('');
$translations = $repo->getTranslations();
$missing_translations = [];
$available_translations = [];
$available_languages = ['fr','en','gb','it'];
$messages = [];

foreach ($translations as $key => $trans) {
    foreach ($available_languages as $lang) {
        $key = str_replace ("<{payplug}prestashop>", "", $key);
        if (!$trans[$lang]) {
            $missing_translations[$lang][$key] = $trans['default'];
        } else {
            $available_translations[$lang][$key]['default'] = $trans['default'];
            $available_translations[$lang][$key]['lang'] = $trans[$lang];
        }

    }
}

// Open a file in write mode ('w')
$fp = fopen('translations.xlsx', 'w');

// Loop through file pointer and a line
foreach($available_translations as $lang => $translations) {
    foreach($translations as $key => $trans) {
        $trans = preg_replace("/\s+/", " ", $trans);
        $line = [ $lang , $key, $trans['default'], $trans['lang']  ];
        fputcsv($fp, $line);
    }
}

fclose($fp);

if (!empty($missing_translations)) {
    $messages[] = '/!\ /!\ /!\ Some translations are missing /!\ /!\ /!\ ';
    foreach($missing_translations as $lang => $translations) {
        $messages[] = 'There is ' . count($translations) . ' translations missing for the language "' . $lang . '":';
        foreach($translations as $key => $trans) {
            $trans = preg_replace("/\s+/", " ", $trans);
            $messages[] = $key . ' => ' . $trans;
        }
        $messages[] = "\n";
    }
}

if (!empty($messages)) {
    foreach ($messages as $message) {
        echo $message . "\n";
    }
    die(1);
}
