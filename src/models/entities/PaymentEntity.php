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

class PaymentEntity
{
    /** @var string */
    private $cart_hash;

    /** @var string */
    private $date_upd;

    /** @var array */
    private static $definition = [
        'table' => 'payplug_payment',
        'primary' => 'id_payplug_payment',
        'fields' => [
            'resource_id' => ['type' => 'string', 'required' => true],
            'is_live' => ['type' => 'boolean', 'required' => true],
            'method' => ['type' => 'string', 'required' => true],
            'id_cart' => ['type' => 'integer', 'required' => true],
            'cart_hash' => ['type' => 'string', 'required' => true],
            'schedules' => ['type' => 'string'],
            'date_upd' => ['type' => 'string'],
        ],
    ];

    /** @var int */
    private $id;

    /** @var int */
    private $id_cart;

    /** @var string */
    private $method;

    /** @var string */
    private $resource_id;

    /** @var string */
    private $schedules;

    /**
     * @return string
     */
    public function getCartHash()
    {
        return $this->cart_hash;
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
     * @return int
     */
    public function getIdCart()
    {
        return $this->id_cart;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getResourceId()
    {
        return $this->resource_id;
    }

    /**
     * @return string
     */
    public function getSchedules()
    {
        return $this->schedules;
    }

    /**
     * @param $cart_hash
     *
     * @return $this
     */
    public function setCartHash($cart_hash)
    {
        if (!is_string($cart_hash)) {
            throw new BadParameterException('Invalid argument, $cart_hash must be a string');
        }

        $this->cart_hash = $cart_hash;

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
            throw new BadParameterException('Invalid argument, $date_upd must be a date format \'y-m-d h:m:s\'');
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
     * @param $id_cart
     *
     * @return $this
     */
    public function setIdCart($id_cart)
    {
        if (!is_int($id_cart)) {
            throw new BadParameterException('Invalid argument, $id_cart must be an int');
        }

        $this->id_cart = $id_cart;

        return $this;
    }

    /**
     * @param $method
     *
     * @return $this
     */
    public function setMethod($method)
    {
        if (!is_string($method)) {
            throw new BadParameterException('Invalid argument, $method must be a string');
        }

        $this->method = $method;

        return $this;
    }

    /**
     * @param $resource_id
     *
     * @return $this
     */
    public function setResourceId($resource_id)
    {
        if (!is_string($resource_id)) {
            throw new BadParameterException('Invalid argument, $resource_id must be a string');
        }

        $this->resource_id = $resource_id;

        return $this;
    }

    /**
     * @param $schedules
     *
     * @return $this
     */
    public function setSchedules($schedules)
    {
        if (!is_string($schedules)) {
            throw new BadParameterException('Invalid argument, $schedules must be a string');
        }

        $this->schedules = $schedules;

        return $this;
    }
}
