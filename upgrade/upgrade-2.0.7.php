<?php
/**
 * 2013 - 2022 PayPlug SAS
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
 *  @copyright 2013 - 2022 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PayPlug SAS
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_0_7($object)
{
    //we cannot allow 1.6 versions tu update from 1.7 content (and vice versa)
    if (version_compare(_PS_VERSION_, '1.7', '>=')) {
        return true;
    }

    //sql
    $req_payplug_lock = '
        ALTER TABLE ' . _DB_PREFIX_ . $object->name . '_lock 
        ADD COLUMN `id_order` VARCHAR(100) 
        AFTER `id_cart`';
    $res_payplug_lock = DB::getInstance()->Execute($req_payplug_lock);

    //files
    //$suppr_files = true;
    //$suppr_dirs = true;
    $old_files = [
        dirname(__FILE__) . '/../classes/PayplugTools.php',
        dirname(__FILE__) . '/../controllers/dispatcher.php',
        dirname(__FILE__) . '/../controllers/FrontAjaxPayplug.php',
        dirname(__FILE__) . '/../controllers/savedCads.php',
        dirname(__FILE__) . '/../css/admin.css',
        dirname(__FILE__) . '/../css/front.css',
        dirname(__FILE__) . '/../css/index.php',
        dirname(__FILE__) . '/../img/index.php',
        dirname(__FILE__) . '/../img/logoPayPlug.png',
        dirname(__FILE__) . '/../img/payplug.png',
        dirname(__FILE__) . '/../img/payplug_en.png',
        dirname(__FILE__) . '/../img/payplug_fr.png',
        dirname(__FILE__) . '/../upgrade/Upgrade-0.9.2.php',
        dirname(__FILE__) . '/../upgrade/Upgrade-0.9.7.php',
        dirname(__FILE__) . '/../upgrade/Upgrade-1.1.0.php',
        dirname(__FILE__) . '/../views/templates/front/cards_list_1_5.tpl',
        dirname(__FILE__) . '/../views/templates/front/cards_list_1_6.tpl',
        dirname(__FILE__) . '/../views/templates/front/maximumAmount.tpl',
        dirname(__FILE__) . '/../views/templates/front/needEuro.tpl',
        dirname(__FILE__) . '/../views/templates/hook/payment.tpl',
        dirname(__FILE__) . '/../views/templates/hook/payment_16.tpl',
        dirname(__FILE__) . '/../installPayplug.php',
        dirname(__FILE__) . '/../ipn.php',
    ];

    $old_dirs = [
        dirname(__FILE__) . '/../css',
        dirname(__FILE__) . '/../img',
    ];

    foreach ($old_files as $file) {
        if (file_exists($file)) {
            //$suppr_files = false;
            if (unlink($file)) {
                //$suppr_files = true;
            } else {
                break;
            }
        }
    }

    foreach ($old_dirs as $dir) {
        if (is_dir($dir)) {
            //$suppr_dirs = false;
            if (rmdir($dir)) {
                //$suppr_dirs = true;
            } else {
                break;
            }
        }
    }

    //return ($res_payplug_lock && $suppr_files && $suppr_dirs);
    return $res_payplug_lock;
}
