<?php

$configuration = json_decode(file_get_contents(dirname(__FILE__)."/../composer.json"));
$moduleName = $configuration->moduleName;
$moduleVersion = $configuration->version;

echo 'Building module with version ' . $moduleVersion . "\n";

$str = implode("\n", file(dirname(__FILE__)."/../".$moduleName.".php"));
$fp = fopen(dirname(__FILE__)."/../".$moduleName.".php", 'w');
$str = str_replace("PAYPLUG_VERSION", "'".$moduleVersion."'", $str);
fwrite($fp, $str, strlen($str));
fclose($fp);

die();
