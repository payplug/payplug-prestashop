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
 * @author    PayPlug SAS
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PayPlug SAS
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_30_0($object)
{
    //we cannot allow 1.6 versions tu update from 1.7 content (and vice versa)
    if (version_compare(_PS_VERSION_, '1.7', '>=')) {
        return true;
    }

    $flag = true;

    try {
        // check if lock exists on id_cart
        $req_describe = 'DESCRIBE ' . _DB_PREFIX_ . $object->name . '_lock;';
        $res_describe = Db::getInstance()->executeS($req_describe);
        $lock_exists = false;
        if ($res_describe) {
            foreach ($res_describe as $field) {
                if ($field['Field'] == 'id_cart' && $field['Key'] == 'UNI') {
                    $lock_exists = true;
                }
            }
        }

        // check doesn't exist then add it
        if (!$lock_exists) {
            $req_truncate = 'TRUNCATE `' . _DB_PREFIX_ . $object->name . '_lock`;';
            $res_truncate = Db::getInstance()->execute($req_truncate);
            if (!$res_truncate) {
                $flag = false;
            }
            if ($flag) {
                $req_alter = 'ALTER TABLE `' . _DB_PREFIX_ . $object->name . '_lock` 
                ADD CONSTRAINT lock_cart_unique UNIQUE (id_cart)';
                $res_alter = Db::getInstance()->execute($req_alter);
                if (!$res_alter) {
                    $flag = false;
                }
            }
            if ($flag) {
                $req_describe = 'DESCRIBE ' . _DB_PREFIX_ . $object->name . '_lock;';
                $res_describe = Db::getInstance()->executeS($req_describe);
                if ($res_describe) {
                    foreach ($res_describe as $field) {
                        if ($field['Field'] == 'id_cart' && $field['Key'] == 'UNI') {
                            $flag = $flag && true;
                        }
                    }
                } else {
                    $flag = false;
                }
            }
        }
    } catch (Exception $e) {
        $flag = false;
    }

    return $flag;
}
