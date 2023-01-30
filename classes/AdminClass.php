<?php
/**
 * 2013 - 2023 PayPlug SAS
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
 * @copyright 2013 - 2023 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\classes;

class AdminClass
{
    private $context;
    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
    }

    /**
     * @param string $controller_name
     * @param int    $id_order
     *
     * @return string
     */
    public function getAdminAjaxUrl($controller_name = 'AdminModules', $id_order = 0)
    {
        if ($controller_name == 'AdminModules') {
            switch ($this->dependencies->name) {
                case 'pspaylater':
                    $admin_ajax_url = $this->context->link->getAdminLink('AdminPsPayLater');

                    break;

                case 'payplug':
                    $admin_ajax_url = $this->context->link->getAdminLink('AdminPayplug');

                    break;
            }
        } elseif ($controller_name == 'AdminOrders') {
            $admin_ajax_url = $this->context->link->getAdminLink($controller_name) . '&id_order=' . $id_order
                . '&vieworder';
        }

        return $admin_ajax_url;
    }

    /**
     * @param string $controller_name
     * @param int    $id_order
     * @param mixed  $params
     *
     * @return string
     */
    public function getAdminUrl($controller_name = 'AdminModules', $params = [])
    {
        if (!empty($params) && !is_array($params)) {
            return false;
        }

        $admin_url = $this->context->link->getAdminLink($controller_name);
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $admin_url .= '&' . $key . (empty($value) ? '' : '=' . $value);
            }
        }

        return $admin_url;
    }
}
