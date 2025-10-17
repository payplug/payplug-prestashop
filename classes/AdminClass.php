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

namespace PayPlug\classes;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminClass
{
    private $context;
    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->context = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get();
    }

    /**
     * @param string $controller_name
     * @param int $id_order
     *
     * @return string
     */
    public function getAdminAjaxUrl($controller_name = 'AdminModules', $id_order = 0)
    {
        $admin_ajax_url = '';
        if ('AdminModules' == $controller_name) {
            switch ($this->dependencies->name) {
                case 'pspaylater':
                    $admin_ajax_url = $this->context->link->getAdminLink('AdminPsPayLater');

                    break;

                case 'payplug':
                    $admin_ajax_url = $this->context->link->getAdminLink('AdminPayplug');

                    break;
            }
        } elseif ('AdminOrders' == $controller_name) {
            $admin_ajax_url = $this->context->link->getAdminLink($controller_name) . '&id_order=' . $id_order
                . '&vieworder';
        }

        return $admin_ajax_url;
    }

    /**
     * @param string $controller_name
     * @param mixed $params
     *
     * @return string
     */
    public function getAdminUrl($controller_name = 'AdminModules', $params = [])
    {
        if (!empty($params) && !is_array($params)) {
            return '';
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
