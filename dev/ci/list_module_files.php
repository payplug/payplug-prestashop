<?php

ini_set('display_errors', true);
require_once(__DIR__ . '/../../vendor/autoload.php');


$target_dir = '';
if (isset($argv) && !empty($argv)) {
    $target_dir = isset($argv[1]) && $argv[1] ? $argv[1] . '/' : '';
}

$list = \PayPlug\src\utilities\helpers\FilesHelper::get($target_dir);
$path = dirname(__FILE__) . '/../../';
$file_name = 'module_files.csv';

// Open a file in write mode ('w')
if ($fp = fopen($path . $file_name, 'w')) {
    fputcsv($fp, ['module_files.csv']);
    foreach ($list as $line) {
        fputcsv($fp, [$line]);
    }
    fclose($fp);
}
