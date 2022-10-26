<?php

$header = '<?php

global $_MODULE;
$_MODULE = [];
';

$configuration = json_decode(file_get_contents(dirname(__FILE__)."/../../composer.json"));
$moduleName = $configuration->moduleName;

$available_languages = ['fr', 'en', 'gb', 'it', 'es'];
$language_index = [];
$translations = [];

// Hydrate $translations from CSV file
$path = dirname(dirname(__FILE__)) . '/dist/' . $moduleName .'/translations.csv';

if (file_exists($path)) {
    if ($csvfile = fopen($path, 'r')) {
        $count = 0;
        while (($line = fgetcsv($csvfile, 0, ';')) !== false) {
            if ($count) {
                $translation_key = reset($line);
                $row = [];
                foreach ($available_languages as $lang) {
                    $index = $language_index[$lang];
                    if (isset($line[$index]) && $line[$index]) {
                        $row[$lang] = $line[$index];
                    }
                }
                $translations[$translation_key] = $row;
            } else {
                foreach ($line as $k => $v) {
                    if (in_array($v, $available_languages)) {
                        $language_index[$v] = $k;
                    }
                }
            }
            $count++;
        }
        fclose($csvfile);
    }
}

// Open translation files from available languages
$files = [];
foreach ($available_languages as $lang) {
    $path_lang = dirname(__FILE__) . '/../../translations/' . $lang . '.php';
    $files[$lang] = fopen($path_lang, 'w');
    fwrite($files[$lang], $header);
}

// Hydrate php file from translations
if (!empty($translations)) {
    ksort($translations);

    foreach ($translations as $translation_key => $translation) {
        foreach ($translation as $lang => $value) {
            if (in_array($lang, $available_languages) && $value) {
                $value = str_replace("’", "'", $value);
                $t = addcslashes($value, "'");
                if ($t && $t != '') {
                    $row = '$_MODULE[\'' . $translation_key . '\'] = \'' . $t . '\';' . PHP_EOL;
                    fwrite($files[$lang], $row);
                }
            }
        }
    }
}

if (!empty($files)) {
    foreach ($files as $file) {
        fclose($file);
    }
}
