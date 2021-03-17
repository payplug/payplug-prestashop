<?php

require_once(dirname(__FILE__) . '/../vendor/autoload.php');

//function p($var) {
//    echo $var . "\n";
//}
//
//$repo = new PayPlug\src\repositories\TranslationsRepository('');
//$translations = $repo->getTranslations();
//$missing_translations = [];
//$available_languages = ['fr','en','gb','it'];
//
//foreach ($translations as $key => $trans) {
//    foreach ($available_languages as $lang) {
//        if (!$trans[$lang]) {
//            $missing_translations[$lang][$key] = $trans['default'];
//        }
//    }
//}
//
//if (!empty($missing_translations)) {
//    p('/!\ /!\ /!\ Some translations are missing /!\ /!\ /!\ ');
//    foreach($missing_translations as $lang => $translations) {
//        p('There is ' . count($translations) . ' translations missing for the language "' . $lang . '":');
//        foreach($translations as $key => $trans) {
//            $trans = preg_replace("/\s+/", " ", $trans);
//            p($key . ' => ' . $trans);
//        }
//    }
//}

die(1);
