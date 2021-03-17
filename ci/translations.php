<?php

require_once(dirname(__FILE__) . '/../vendor/autoload.php');

$repo = new PayPlug\src\repositories\TranslationsRepository('');
$translations = $repo->getTranslations();
$missing_translations = [];
$available_languages = ['fr','en','gb','it'];

$messages = [];

foreach ($translations as $key => $trans) {
    foreach ($available_languages as $lang) {
        if (!$trans[$lang]) {
            $missing_translations[$lang][$key] = $trans['default'];
        }
    }
}

if (!empty($missing_translations)) {
    $messages[] = '/!\ /!\ /!\ Some translations are missing /!\ /!\ /!\ ';
    foreach($missing_translations as $lang => $translations) {
        $messages[] = 'There is ' . count($translations) . ' translations missing for the language "' . $lang . '":';
        foreach($translations as $key => $trans) {
            $trans = preg_replace("/\s+/", " ", $trans);
            $messages[] = $key . ' => ' . $trans;
        }
    }
}

if (!empty($messages)) {
    foreach ($messages as $message) {
        echo $message . "\n";
    }
    die(1);
}
