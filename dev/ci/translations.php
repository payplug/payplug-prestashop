<?php

require_once(dirname(__FILE__) . '/src/Translations.php');

$repo = new Translations();
$translations = $repo->getTranslations();
$moduleName = $repo->getModuleName();

ksort($translations);

$missing_translations = [];
$available_languages = ['fr', 'en', 'gb', 'it'];
$messages = [];

// Open a file in write mode ('w')
$fp = fopen(dirname(__FILE__) . '../../dist/' . $moduleName . '/translations.csv', 'w');
$header = ['key', 'default', 'tags'];
$header = array_merge($header, $available_languages);

if ($fp) {
    fputcsv($fp, $header, ';');
    foreach ($translations as $key => $trans) {
        $key = str_replace("<{'. $moduleName .'}prestashop>", "", $key);

        $line = [$key, $trans['default'], $trans['tags']];
        foreach ($available_languages as $lang) {
            $line[] = stripcslashes($trans[$lang]);

            if (!$trans[$lang] && strpos($key, 'pspaylater') == false && $moduleName != 'pspaylater') {
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

// In case we use this script via the CI, check if we need a strong feed back
$need_return = false;
if (isset($argv) && !empty($argv)) {
    $target_branch = isset($argv[1]) ? $argv[1] : false;
    if ($target_branch) {
        $restricted_branches = ['qa','hotfix','master','release'];
        foreach ($restricted_branches as $branch) {
            $pos = strpos($target_branch, $branch);
            if ($pos !== false && !$pos && !$need_return) {
                $need_return = true;
            }
        }
    }
}

// Return error message needed
if (!empty($messages)) {
    echo 'Translation missing' . "\n";
    foreach ($messages as $message) {
        echo $message . "\n";
    }
    if ($need_return) {
        exit(1);
    } else {
        exit(137);
    }
}
echo 'No translation missing' . "\n";
exit(0);
