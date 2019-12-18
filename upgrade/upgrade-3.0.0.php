<?php
/**
 * 2013 - 2019 PayPlug SAS
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
 *  @copyright 2013 - 2019 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

require_once(_PS_MODULE_DIR_ . 'payplug/classes/MyLogPHP.class.php');

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_0_0()
{
    truncateLogfile(_PS_MODULE_DIR_ . 'payplug/log/general-log.csv');
    truncateLogfile(_PS_MODULE_DIR_ . 'payplug/log/inst_ipn-*.csv');
    truncateLogfile(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
    truncateLogfile(_PS_MODULE_DIR_ . 'payplug/log/ipn-*.csv');
    truncateLogfile(_PS_MODULE_DIR_ . 'payplug/log/notification-*.csv');
    truncateLogfile(_PS_MODULE_DIR_ . 'payplug/log/prepare_payment.csv');
    truncateLogfile(_PS_MODULE_DIR_ . 'payplug/log/validation-*.csv');
    return true;
}
