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

class OperationEntity
{
    /** @var int */
    private $amount;

    /** @var string */
    private $date_add;

    /** @var string */
    private $date_upd;

    /** @var array */
    private static $definition = [
        'table' => 'payplug_upc_operation',
        'primary' => 'id_payplug_upc_operation',
        'fields' => [
            'operation_id' => ['type' => 'string', 'required' => true],
            'order_id' => ['type' => 'string', 'required' => true],
            'exec_code' => ['type' => 'string', 'required' => true],
            'outcome' => ['type' => 'string', 'required' => true],
            'amount' => ['type' => 'integer', 'required' => true],
            'treated' => ['type' => 'boolean'],
            'date_add' => ['type' => 'string'],
            'date_upd' => ['type' => 'string'],
        ],
    ];

    /** @var string */
    private $exec_code;

    /** @var int */
    private $id;

    /** @var string */
    private $operation_id;

    /** @var string */
    private $order_id;

    /** @var string */
    private $outcome;

    /** @var bool */
    private $treated;

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
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
     * @return string
     */
    public function getExecCode()
    {
        return $this->exec_code;
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
    public function getOperationId()
    {
        return $this->operation_id;
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
    public function getOutcome()
    {
        return $this->outcome;
    }

    /**
     * @return bool
     */
    public function getTreated()
    {
        return $this->treated;
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
     * @param $exec_code
     *
     * @return $this
     */
    public function setExecCode($exec_code)
    {
        if (!is_string($exec_code)) {
            throw new BadParameterException('Invalid argument, $exec_code must be a string');
        }

        $this->exec_code = $exec_code;

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
     * @param $operation_id
     *
     * @return $this
     */
    public function setOperationId($operation_id)
    {
        if (!is_string($operation_id)) {
            throw new BadParameterException('Invalid argument, $operation_id must be a string');
        }

        $this->operation_id = $operation_id;

        return $this;
    }

    /**
     * @param $order_id
     *
     * @return $this
     */
    public function setOrderId($order_id)
    {
        if (!is_string($order_id)) {
            throw new BadParameterException('Invalid argument, $order_id must be a string');
        }

        $this->order_id = $order_id;

        return $this;
    }

    /**
     * @param $outcome
     *
     * @return $this
     */
    public function setOutcome($outcome)
    {
        if (!is_string($outcome)) {
            throw new BadParameterException('Invalid argument, $outcome must be a string');
        }

        $this->outcome = $outcome;

        return $this;
    }

    /**
     * @param $treated
     *
     * @return $this
     */
    public function setTreated($treated)
    {
        if (!is_bool($treated)) {
            throw new BadParameterException('Invalid argument, $treated must be a boolean');
        }

        $this->treated = $treated;

        return $this;
    }
}
