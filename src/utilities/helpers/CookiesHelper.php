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

namespace PayPlug\src\utilities\helpers;

class CookiesHelper
{
    private $dependencies;

    public function __construct($dependencies = null)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @description Get payment data from cookie
     *
     * @return string
     */
    public function getPaymentDataCookie()
    {
        $context = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get();
        // get payplug data
        $cookie_data = $context->cookie->__get($this->dependencies->name . '_data');
        $payplug_data = !empty($cookie_data) ? $cookie_data : false;

        // then flush to avoid repetition
        $context->cookie->__set($this->dependencies->name . '_data', '');

        // if no error all good then return true
        return json_decode($payplug_data, true);
    }

    /**
     * @description Get payment errors from cookie
     *
     * @return string
     */
    public function getPaymentErrorsCookie()
    {
        $context = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get();

        // get payplug errors
        $cookie_errors = $context->cookie->__get($this->dependencies->name . 'Errors');
        $payplug_errors = !empty($cookie_errors) ? $cookie_errors : false;

        // then flush to avoid repetition
        $context->cookie->__set($this->dependencies->name . 'Errors', '');

        // if no error all good then return true
        return json_decode($payplug_errors, true);
    }

    /**
     * @description Set payment data in cookie
     *
     * @param array $payplug_data
     *
     * @return bool
     */
    public function setPaymentDataCookie($payplug_data = [])
    {
        if (empty($payplug_data)) {
            return false;
        }

        $context = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get();

        $value = json_encode($payplug_data);

        $context->cookie->__set($this->dependencies->name . '_data', $value);

        return (bool) $context->cookie->__get($this->dependencies->name . '_data');
    }

    /**
     * @description Set payment errors in cookie
     *
     * @param array $payplug_errors
     *
     * @return bool
     */
    public function setPaymentErrorsCookie($payplug_errors = [])
    {
        if (empty($payplug_errors)) {
            return false;
        }

        $context = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get();

        // Check if already setted
        if ((bool) $context->cookie->__get($this->dependencies->name . 'Errors')) {
            return true;
        }

        $value = json_encode($payplug_errors);

        $context->cookie->__set($this->dependencies->name . 'Errors', $value);

        return (bool) $context->cookie->__get($this->dependencies->name . 'Errors');
    }
}
