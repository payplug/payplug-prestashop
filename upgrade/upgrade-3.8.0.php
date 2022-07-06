<?php
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
}

function upgrade_module_3_8_0($object)
{
    $flag = true;

    // add  PAYPLUG_APPLEPAY to database
    $flag = $flag && Configuration::updateValue(
        'PAYPLUG_APPLEPAY',
        null
    );

    $auto_capture = Configuration::get('PAYPLUG_DEFERRED_AUTO');
    $deferred_state = Configuration::get('PAYPLUG_DEFERRED_STATE');

    if (!$auto_capture && !$deferred_state) {
        // if auto capture for deferred payment is disable AND a state is set in the configuration
        // then we reset the deferred state
        $flag = $flag && Configuration::updateValue('PAYPLUG_DEFERRED_STATE', 0);
    }
    $flag = $flag && Configuration::deleteByName('PAYPLUG_DEFERRED_AUTO');

    // Uninstall current AdminTab to avoid dupplication
    $sql = 'SELECT `id_tab` FROM `' . _DB_PREFIX_ . 'tab` WHERE `module` = "payplug"';
    $tabIds = Db::getInstance()->execute($sql);
    if ($tabIds) {
        foreach ($tabIds as $tabId) {
            $tab = new Tab($tabId);
            $flag = $flag && $tab->delete();
        }
    }

    return $flag;
}
