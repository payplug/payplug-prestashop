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

class QueueEntity
{
    /** @var string */
    private $date_add;

    /** @var string */
    private $date_upd;

    /** @var int */
    private $id;

    /** @var int */
    private $id_cart;

    /** @var string */
    private $resource_id;

    /** @var bool */
    private $treated;

    /** @var string */
    private $type;

    /** @var array */
    private static $definition = [
        'table' => 'payplug_queue',
        'primary' => 'id_payplug_queue',
        'fields' => [
            'date_add' => ['type' => 'string'],
            'date_upd' => ['type' => 'string'],
            'id_cart' => ['type' => 'integer', 'required' => true],
            'resource_id' => ['type' => 'string', 'required' => true],
            'treated' => ['type' => 'boolean'],
            'type' => ['type' => 'string'],
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
    public function getResourceId()
    {
        return $this->resource_id;
    }

    /**
     * @return bool
     */
    public function getTreated()
    {
        return $this->treated;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $date_add
     *
     * @return $this
     */
    public function setDateAdd($date_add)
    {
        if (!is_string($date_add) || !preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date_add)) {
            throw new BadParameterException('Invalid argument, $date_add must be a date format \'y-m-d h:m:s\'');
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
     * @param $treated
     *
     * @return $this
     */
    public function setTreated($treated)
    {
        if (!is_bool($treated)) {
            throw new BadParameterException('Invalid argument, $treated must be a bool');
        }

        $this->treated = $treated;

        return $this;
    }

    /**
     * @param $type
     *
     * @return $this
     */
    public function setType($type)
    {
        if (!is_string($type)) {
            throw new BadParameterException('Invalid argument, $type must be a string');
        }

        $this->type = $type;

        return $this;
    }
}
