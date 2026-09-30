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

class AliasEntity
{
    /** @var string */
    private $alias_id;

    /** @var string|null */
    private $brand;

    /** @var string */
    private $currency;

    /** @var string */
    private $date_add;

    /** @var array */
    private static $definition = [
        'table' => 'payplug_alias',
        'primary' => 'id_payplug_alias',
        'fields' => [
            'id_customer' => ['type' => 'integer', 'required' => true],
            'alias_id' => ['type' => 'string', 'required' => true],
            'currency' => ['type' => 'string', 'required' => true],
            'identifier' => ['type' => 'string', 'required' => true],
            'brand' => ['type' => 'string'],
            'last4' => ['type' => 'string'],
            'exp_month' => ['type' => 'string'],
            'exp_year' => ['type' => 'string'],
            'date_add' => ['type' => 'string'],
        ],
    ];

    /** @var string|null */
    private $exp_month;

    /** @var string|null */
    private $exp_year;

    /** @var int */
    private $id;

    /** @var int */
    private $id_customer;

    /** @var string */
    private $identifier;

    /** @var string|null */
    private $last4;

    /**
     * @description Get the Unified API alias id
     *
     * @return string
     */
    public function getAliasId()
    {
        return $this->alias_id;
    }

    /**
     * @description Get the card brand
     *
     * @return string|null
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @description Get the lowercase ISO currency code
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @description Get the alias creation date
     *
     * @return string with a specific pattern matching 'yyyy-mm-dd hh:mm:ss'
     */
    public function getDateAdd()
    {
        return $this->date_add;
    }

    /**
     * @description Get the alias entity definition
     *
     * @return array
     */
    public function getDefinition()
    {
        return self::$definition;
    }

    /**
     * @description Get the card expiry month
     *
     * @return string|null
     */
    public function getExpMonth()
    {
        return $this->exp_month;
    }

    /**
     * @description Get the card expiry year
     *
     * @return string|null
     */
    public function getExpYear()
    {
        return $this->exp_year;
    }

    /**
     * @description Get the alias id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @description Get the customer id
     *
     * @return int
     */
    public function getIdCustomer()
    {
        return $this->id_customer;
    }

    /**
     * @description Get the UHF identifier the alias was created with
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @description Get the card last 4 digits
     *
     * @return string|null
     */
    public function getLast4()
    {
        return $this->last4;
    }

    /**
     * @description Set the Unified API alias id
     *
     * @param string $alias_id
     *
     * @return $this
     */
    public function setAliasId($alias_id)
    {
        if (!is_string($alias_id) || !$alias_id) {
            throw new BadParameterException('Invalid argument, $alias_id must be a non empty string');
        }

        $this->alias_id = $alias_id;

        return $this;
    }

    /**
     * @description Set the card brand
     *
     * @param string $brand
     *
     * @return $this
     */
    public function setBrand($brand)
    {
        if (!is_string($brand)) {
            throw new BadParameterException('Invalid argument, $brand must be a string');
        }

        $this->brand = $brand;

        return $this;
    }

    /**
     * @description Set the lowercase ISO currency code
     *
     * @param string $currency
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
     * @description Set the alias creation date
     *
     * @param string $date_add
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
     * @description Set the card expiry month
     *
     * @param string $exp_month
     *
     * @return $this
     */
    public function setExpMonth($exp_month)
    {
        if (!is_string($exp_month)) {
            throw new BadParameterException('Invalid argument, $exp_month must be a string');
        }

        $this->exp_month = $exp_month;

        return $this;
    }

    /**
     * @description Set the card expiry year
     *
     * @param string $exp_year
     *
     * @return $this
     */
    public function setExpYear($exp_year)
    {
        if (!is_string($exp_year)) {
            throw new BadParameterException('Invalid argument, $exp_year must be a string');
        }

        $this->exp_year = $exp_year;

        return $this;
    }

    /**
     * @description Set the alias id
     *
     * @param int $id
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
     * @description Set the customer id
     *
     * @param int $id_customer
     *
     * @return $this
     */
    public function setIdCustomer($id_customer)
    {
        if (!is_int($id_customer)) {
            throw new BadParameterException('Invalid argument, $id_customer must be an int');
        }

        $this->id_customer = $id_customer;

        return $this;
    }

    /**
     * @description Set the UHF identifier the alias was created with
     *
     * @param string $identifier
     *
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        if (!is_string($identifier) || !$identifier) {
            throw new BadParameterException('Invalid argument, $identifier must be a non empty string');
        }

        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @description Set the card last 4 digits
     *
     * @param string $last4
     *
     * @return $this
     */
    public function setLast4($last4)
    {
        if (!is_string($last4)) {
            throw new BadParameterException('Invalid argument, $last4 must be a string');
        }

        $this->last4 = $last4;

        return $this;
    }
}
