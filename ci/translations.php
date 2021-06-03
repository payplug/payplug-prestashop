<?php
require_once(dirname(__FILE__) . '/../vendor/autoload.php');

$repo = new PayPlug\src\repositories\TranslationsRepository('');
$translations = $repo->getTranslations();
$missing_translations = [];
$available_languages = ['fr', 'en', 'gb', 'it'];
$messages = [];

// Open a file in write mode ('w')
$fp = fopen(dirname(__FILE__) . '/translations.csv', 'w');
$header = ['key', 'default'];
$header = array_merge($header, $available_languages);

if ($fp) {
    fputcsv($fp, $header, ';');
    foreach ($translations as $key => $trans) {
        $key = str_replace("<{payplug}prestashop>", "", $key);
        $line = [$key, $trans['default']];
        foreach ($available_languages as $lang) {
            $line[] = stripcslashes($trans[$lang]);

            if (!$trans[$lang]) {
                $missing_translations[$lang][$key] = $trans['default'];
            }
        }
        fputcsv($fp, $line, ';');
    }
    fclose($fp);
}

// Show missing translation
if (!empty($missing_translations)) {
    $messages[] = '/!\ /!\ /!\ Some translations are missing /!\ /!\ /!\ ';
    foreach ($missing_translations as $lang => $translations) {
        $messages[] = 'There is ' . count($translations) . ' translations missing for the language "' . $lang . '":';
        foreach ($translations as $key => $trans) {
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
}
