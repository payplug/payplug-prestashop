<?php

require_once(dirname(__FILE__) . '/../../upgrade/upgrade-methods.php');

function getMethodContent($method_name = false)
{
    if (!$method_name || !is_string($method_name)) {
        return '';
    }

    $func = new ReflectionFunction($method_name);
    $filename = $func->getFileName();
    $start_line = $func->getStartLine() + 1; // it's actually - 1, otherwise you wont get the function() block
    $end_line = $func->getEndLine() - 1;
    $length = $end_line - $start_line;

    $source = file($filename);
    $body = implode("", array_slice($source, $start_line, $length));

    return $body;
}

$configuration = json_decode(file_get_contents(dirname(__FILE__)."/../../composer.json"));
$moduleName = $configuration->moduleName;

// get versions to create
$methods = get_defined_functions();
$versions = [];
foreach ($methods['user'] as $method) {
    $pattern = "/upgrade_module_payplug_\s*((?:[0-9]+\_?)+)/i";
    preg_match($pattern, $method, $matches);
    if (empty($matches)) {
        continue;
    }
    $versions[] = str_replace('_', '.', $matches[1]);
}

if (empty($versions)) {
    echo 'There is no update to create' . "\n";
    die();
}

$header = "<?php
/**
 * 2013 - 2021 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    PayPlug SAS
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

if (!defined('_PS_VERSION_')) {
    exit;
}";

foreach ($versions as $version) {
    echo 'Create upgrade script for version: ' . $version . "\n";

    $file_name = 'upgrade-' . $version . '.php';
    $method_name =  'upgrade_module_'
        . strtolower($moduleName) . '_'
        . str_replace('.', '_', $version);

    if (!function_exists($method_name)) {
        echo 'No upgrade script for version ' . $version . "\n";
        continue;
    }

    $content = $header . '
    function ' . $method_name . '($object)
    {
        $flag = true;
        
        ' . getMethodContent($method_name) . '
        
        return $flag;
    }
';

    echo 'Writing script ' . $file_name . '...' . "\n";
    $path = dirname(__FILE__) . '/../../upgrade/' . $file_name;
    if ($file = fopen($path, 'w')) {
        fwrite($file, $content);
        fclose($file);
    }

    if (file_exists($path)) {
        echo 'Update script created: ' . $file_name . '...' . "\n";
    } else {
        echo 'An error occured during script creation';
        die(1);
    }
}

die();
