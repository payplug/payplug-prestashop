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
 * @author    PayPlug SAS
 * @copyright 2013 - 2020 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class PPPayment
{
    public $resource;

    public function __construct($id = null)
    {
        if ($id != null) {
            $payment = $this->retrieve($id);
            $this->populateFromPayment($payment);
        } else {
            $id = null;
            $resource = null;
        }
    }

    public function retrieve($id)
    {
        try {
            $payment = \Payplug\Payment::retrieve($id);
        } catch (\Payplug\Exception $e) {
            $data = array(
                'result' => false,
                'response' => $e->__toString(),
            );
            return $data;
        }
        return $payment;
    }

    private function populateFromPayment($payment)
    {
        $this->resource = $payment;
    }

    public function capture()
    {
        try {
            $capture = \Payplug\Payment::capture($this->resource->id);
            $response = array(
                'code' => 200,
                'message' => 'Amount successfully captured.',
                'resource' => $this,
            );
        } catch (Payplug\Exception\NotAllowedException $e) {
            $httpResponse = json_decode($e->getHttpResponse());
            $response = array(
                'code' => (int)$e->getCode(),
                'message' => $httpResponse->message,
                'resource' => $this,
            );
        } catch (Payplug\Exception\ForbiddenException $e) {
            $httpResponse = json_decode($e->getHttpResponse());
            $response = array(
                'code' => (int)$e->getCode(),
                'message' => $httpResponse->message,
                'resource' => $this,
            );
        }
        return $response;
    }

    public function isPaid()
    {
        return $this->resource->is_paid;
    }

    public function isDeferred()
    {
        return ($this->resource->authorization !== null);
    }

    public function refresh()
    {
        $payment = $this->retrieve($this->resource->id);
        $this->populateFromPayment($payment);
        return $this;
    }
}
