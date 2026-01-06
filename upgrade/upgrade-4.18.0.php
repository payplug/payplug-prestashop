<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS.
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
 * Do not edit or add to this file if you wish to upgrade Payplug module to newer
 * versions in the future.
 *
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * International Registered Trademark & Property of Payplug SAS
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_4_18_0($object)
{
    $flag = true;

    $logger = $object->module->getPlugin()->getLogger();
    $logger->addLog('Start upgrade script 4.19.0');

    $flag = $flag && Configuration::updateValue('PAYPLUG_OAUTH_CLIENT_DATA', '{}');
    $flag = $flag && Configuration::updateValue('PAYPLUG_JWT', '{}');
    $flag = $flag && Configuration::updateValue('PAYPLUG_OAUTH_CLIENT_ID', '');
    $flag = $flag && Configuration::updateValue('PAYPLUG_OAUTH_CODE_VERIFIER', '');
    $flag = $flag && Configuration::updateValue('PAYPLUG_OAUTH_COMPANY_ID', '');

    $logger->addLog('End upgrade script 4.19.0, result: ' . ($flag ? 'ok' : 'ko'));

    return $flag;
}
