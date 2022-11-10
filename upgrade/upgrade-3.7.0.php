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
 * International Registered Trademark & Property of PayPlug SAS
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_7_0($object)
{
    $flag = true;

    // Set type to native OrderState
    $prestashop_order_states = [
        'PS_OS_PAYMENT' => 'paid',
        'PS_OS_WS_PAYMENT' => 'nothing',
        'PS_OS_CANCELED' => 'cancelled',
        'PS_OS_REFUND' => 'refund',
        'PS_OS_ERROR' => 'error',
        'PS_OS_CHEQUE' => 'nothing',
        'PS_OS_BANKWIRE' => 'nothing',
        'PS_OS_PREPARATION' => 'nothing',
        'PS_OS_SHIPPING' => 'nothing',
        'PS_OS_DELIVERED' => 'nothing',
    ];
    if (version_compare(_PS_VERSION_, '1.6.0.14', '<')) {
        $prestashop_order_states += [
            'PS_OS_OUTOFSTOCK' => 'nothing',
        ];
    } else {
        $prestashop_order_states += [
            'PS_OS_OUTOFSTOCK_PAID' => 'paid',
            'PS_OS_OUTOFSTOCK_UNPAID' => 'pending',
            'PS_OS_COD_VALIDATION' => 'nothing',
        ];
    }

    $date = date('Y-m-d');
    $payplug_order_states_sql = [];
    foreach ($prestashop_order_states as $key => $type) {
        $id_order_state = (int) Configuration::get(($key));
        $getTypeQuery = ' 
            SELECT `type` 
            FROM `' . _DB_PREFIX_ . $object->name . '_order_state` 
            WHERE  `id_order_state` = ' . $id_order_state;
        $sqlGetType = Db::getInstance()->executeS($getTypeQuery);
        if (!$sqlGetType) {
            $payplug_order_states_sql[] = '
            INSERT INTO `' . _DB_PREFIX_ . $object->name . '_order_state` 
            (`id_order_state`, `type`, `date_add`, `date_upd`)
            VALUES (' . $id_order_state . ', "' . $type . '", "' . $date . '", "' . $date . '")';
        }
    }

    if ($payplug_order_states_sql) {
        foreach ($payplug_order_states_sql as $sql) {
            $flag = $flag && Db::getInstance()->execute($sql);
            unset($sql);
        }
    }

    return $flag;
}
