<?php

$configuration = json_decode(file_get_contents(dirname(__FILE__)."/../composer.json"));
$moduleName = $configuration->moduleName;
$moduleVersion = $configuration->version;

echo 'Building module with version ' . $moduleVersion . "\n";
$path = dirname(__FILE__)."/../".$moduleName.".php";
$str = file_get_contents($path);
$fp = fopen($path, 'w');
$str = str_replace("PAYPLUG_VERSION", "'" . $moduleVersion . "'", $str);
fwrite($fp, $str, strlen($str));
fclose($fp);

die();
