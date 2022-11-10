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
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\classes;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PPPayment
{
    public $dependencies;
    public $resource;

    public function __construct($id = null, $dependencies = null)
    {
        $this->dependencies = $dependencies;

        if ($id) {
            $payment = $this->retrieve($id);
            $this->populateFromPayment($payment);
        } else {
            $this->resource = null;
        }
    }

    public function retrieve($id)
    {
        $payment = $this->dependencies->apiClass->retrievePayment($id);
        if (!$payment['result']) {
            return [
                'result' => false,
                'response' => $payment['message'],
            ];
        }

        return $payment['resource'];
    }

    public function capture()
    {
        return $this->dependencies->apiClass->capturePayment($this->resource->id);
    }

    public function isPaid()
    {
        return $this->resource->is_paid;
    }

    public function isDeferred()
    {
        return $this->resource->authorization !== null;
    }

    public function refresh()
    {
        $payment = $this->retrieve($this->resource->id);
        $this->populateFromPayment($payment);

        return $this;
    }

    private function populateFromPayment($payment)
    {
        $this->resource = $payment;
    }
}
