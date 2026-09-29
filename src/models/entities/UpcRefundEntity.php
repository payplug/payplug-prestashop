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

namespace PayPlug\src\models\entities;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\src\exceptions\BadParameterException;

class UpcRefundEntity
{
    /** @var int */
    private $amount;

    /** @var string */
    private $currency;

    /** @var string */
    private $date_add;

    /** @var string */
    private $date_upd;

    /** @var array */
    private static $definition = [
        'table' => 'payplug_upc_refund',
        'primary' => 'id_payplug_upc_refund',
        'fields' => [
            'refund_operation_id' => ['type' => 'string', 'required' => true],
            'payment_operation_id' => ['type' => 'string', 'required' => true],
            'order_id' => ['type' => 'string', 'required' => true],
            'amount' => ['type' => 'integer', 'required' => true],
            'currency' => ['type' => 'string', 'required' => true],
            'status' => ['type' => 'string', 'required' => true],
            'date_add' => ['type' => 'string'],
            'date_upd' => ['type' => 'string'],
        ],
    ];

    /** @var int */
    private $id;

    /** @var string */
    private $order_id;

    /** @var string */
    private $payment_operation_id;

    /** @var string */
    private $refund_operation_id;

    /** @var string */
    private $status;

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return string with a specific pattern matching 'yyyy-mm-dd hh:mm:ss'
     */
    public function getDateAdd()
    {
        return $this->date_add;
    }

    /**
     * @return string with a specific pattern matching 'yyyy-mm-dd hh:mm:ss'
     */
    public function getDateUpd()
    {
        return $this->date_upd;
    }

    /**
     * @return array
     */
    public function getDefinition()
    {
        return self::$definition;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @return string
     */
    public function getPaymentOperationId()
    {
        return $this->payment_operation_id;
    }

    /**
     * @return string
     */
    public function getRefundOperationId()
    {
        return $this->refund_operation_id;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $amount
     *
     * @return $this
     */
    public function setAmount($amount)
    {
        if (!is_int($amount)) {
            throw new BadParameterException('Invalid argument, $amount must be an int');
        }

        $this->amount = $amount;

        return $this;
    }

    /**
     * @param $currency
     *
     * @return $this
     */
    public function setCurrency($currency)
    {
        if (!is_string($currency) || !$currency) {
            throw new BadParameterException('Invalid argument, $currency must be a non empty string');
        }

        $this->currency = $currency;

        return $this;
    }

    /**
     * @param $date_add
     *
     * @return $this
     */
    public function setDateAdd($date_add)
    {
        if (!is_string($date_add) || !preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date_add)) {
            throw new BadParameterException('Invalid argument, $date_add must be at format: \'Y-m-d h:m:s\'');
        }

        $this->date_add = $date_add;

        return $this;
    }

    /**
     * @param $date_upd
     *
     * @return $this
     */
    public function setDateUpd($date_upd)
    {
        if (!is_string($date_upd) || !preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date_upd)) {
            throw new BadParameterException('Invalid argument, $date_upd must be at format: \'Y-m-d h:m:s\'');
        }

        $this->date_upd = $date_upd;

        return $this;
    }

    /**
     * @param $id
     *
     * @return $this
     */
    public function setId($id)
    {
        if (!is_int($id)) {
            throw new BadParameterException('Invalid argument, $id must be an int');
        }

        $this->id = $id;

        return $this;
    }

    /**
     * @param $order_id
     *
     * @return $this
     */
    public function setOrderId($order_id)
    {
        if (!is_string($order_id) || !$order_id) {
            throw new BadParameterException('Invalid argument, $order_id must be a non empty string');
        }

        $this->order_id = $order_id;

        return $this;
    }

    /**
     * @param $payment_operation_id
     *
     * @return $this
     */
    public function setPaymentOperationId($payment_operation_id)
    {
        if (!is_string($payment_operation_id) || !$payment_operation_id) {
            throw new BadParameterException('Invalid argument, $payment_operation_id must be a non empty string');
        }

        $this->payment_operation_id = $payment_operation_id;

        return $this;
    }

    /**
     * @param $refund_operation_id
     *
     * @return $this
     */
    public function setRefundOperationId($refund_operation_id)
    {
        if (!is_string($refund_operation_id) || !$refund_operation_id) {
            throw new BadParameterException('Invalid argument, $refund_operation_id must be a non empty string');
        }

        $this->refund_operation_id = $refund_operation_id;

        return $this;
    }

    /**
     * @param $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        if (!is_string($status) || !$status) {
            throw new BadParameterException('Invalid argument, $status must be a non empty string');
        }

        $this->status = $status;

        return $this;
    }
}
