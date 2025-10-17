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
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */

namespace PayPlug\src\application\adapter;

if (!defined('_PS_VERSION_')) {
    exit;
}
use PayPlug\src\interfaces\TabInterface;

class TabAdapter implements TabInterface
{
    public function get($tab_id = 0)
    {
        if (!is_int($tab_id) || !$tab_id) {
            $tab_id = 0;
        }

        return new \Tab($tab_id);
    }

    public function getIdFromClassName($class_name = '')
    {
        if (!is_string($class_name) || !$class_name) {
            $class_name = '';
        }

        return \Tab::getIdFromClassName($class_name);
    }
}
