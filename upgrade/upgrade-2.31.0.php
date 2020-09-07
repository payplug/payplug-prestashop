<?php
/**
 * 2013 - 2020 PayPlug SAS
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
 *  @copyright 2013 - 2020 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

require_once(_PS_MODULE_DIR_ . 'payplug/classes/MyLogPHP.class.php');

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_31_0()
{
    $is_applied_constraint = false;
    try {
        $req_truncate = 'TRUNCATE `'._DB_PREFIX_.'payplug_lock`;';
        $res_truncate = Db::getInstance()->Execute($req_truncate);
        if ($res_truncate) {
            $req_alter = 'ALTER TABLE `'._DB_PREFIX_.'payplug_lock` ADD CONSTRAINT lock_cart_unique UNIQUE (id_cart)';
            $res_alter = Db::getInstance()->Execute($req_alter);
            if ($req_alter) {
                $req_describe = 'DESCRIBE '._DB_PREFIX_.'payplug_lock;';
                $res_describe = Db::getInstance()->ExecuteS($req_describe);
                if ($res_describe) {
                    foreach ($res_describe as $field) {
                        if ($field['Field'] == 'id_cart' && $field['Key'] == 'UNI') {
                            $is_applied_constraint = true;
                        }
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    } catch (Exception $e) {
        return false;
    }
    if ($is_applied_constraint) {
        return true;
    } else {
        return false;
    }
}
