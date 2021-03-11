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
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2021 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_1_0()
{
    $flag = true;

    //we cannot allow 1.6 versions tu update from 1.7 content (and vice versa)
    if (version_compare(_PS_VERSION_, '1.7', '<')) {
        return $flag;
    }

    // Update payplug card table
    $sql_requests = [
        'ALTER TABLE `'._DB_PREFIX_.'payplug_card` CHANGE `id_payplug_card` `position` INT(11) UNSIGNED NOT NULL',
        'ALTER TABLE `'._DB_PREFIX_.'payplug_card` DROP PRIMARY KEY',
        'ALTER TABLE `'._DB_PREFIX_.'payplug_card` DROP `position`',
        'ALTER TABLE `'._DB_PREFIX_.'payplug_card` ADD `id_payplug_card` INT(11) NOT NULL AUTO_INCREMENT FIRST, 
        ADD PRIMARY KEY (`id_payplug_card`)',
    ];

    try {
        foreach ($sql_requests as $sql_request) {
            $request = Db::getInstance()->execute($sql_request);
            if (!$request) {
                $flag = false;
            }
        }
    } catch (PrestaShopDatabaseException $e) {
        $flag = false;
    }

    return $flag;
}
