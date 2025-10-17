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

class LockEntity
{
    /** @var int */
    private $id;

    /** @var int */
    private $id_cart;

    /** @var string */
    private $id_order;

    /** @var string with a specific pattern matching 'yyyy-mm-dd hh:mm:ss' */
    private $date_add;

    /** @var string with a specific pattern matching 'yyyy-mm-dd hh:mm:ss' */
    private $date_upd;

    /** @var array */
    private static $definition = [
        'table' => 'payplug_lock',
        'primary' => 'id_payplug_lock',
        'fields' => [
            'id_cart' => ['type' => 'integer', 'required' => true],
            'id_order' => ['type' => 'string', 'required' => true],
            'date_add' => ['type' => 'string'],
            'date_upd' => ['type' => 'string'],
        ],
    ];

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
     * @return int
     */
    public function getIdCart()
    {
        return $this->id_cart;
    }

    /**
     * @return string
     */
    public function getIdOrder()
    {
        return $this->id_order;
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
            throw new BadParameterException('Invalid argument, $id must be an integer');
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
            throw new BadParameterException('Invalid argument, $id_cart must be an integer.');
        }

        $this->id_cart = $id_cart;

        return $this;
    }

    /**
     * @param $id_order
     *
     * @return $this
     */
    public function setIdOrder($id_order)
    {
        if (!is_string($id_order)) {
            throw new BadParameterException('Invalid argument, $id_order must be a string.');
        }

        $this->id_order = $id_order;

        return $this;
    }
}
