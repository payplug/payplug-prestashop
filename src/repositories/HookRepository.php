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

namespace PayPlug\src\repositories;

class HookRepository extends Repository
{
    public function _construct($payplug)
    {
        $this->payplug = $payplug;
    }

    public function actionAdminControllerSetMedia()
    {
        if ($this->payplug->context->controller->controller_name == 'AdminOrders') {
            $this->setMedia([
                __PS_BASE_URI__ . 'modules/payplug/views/css/admin_order.css',
                __PS_BASE_URI__ . 'modules/payplug/views/js/admin_order.js',
            ]);
        } else {
            $this->payplug->setMedia([
                __PS_BASE_URI__ . 'modules/payplug/views/js/admin.js',
                __PS_BASE_URI__ . 'modules/payplug/views/css/admin.css',
            ]);
        }
    }

    public function exe($method = false, $params = false)
    {
        if (!$method || !is_string($method)) {
            return false;
        }

        if (!is_array($params)) {
            return false;
        }

        if (!method_exists($this, $method)) {
            return false;
        }

        return $this->$method($params);
    }
}
