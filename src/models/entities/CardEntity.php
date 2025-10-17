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

class CardEntity
{
    /** @var int */
    private $id;

    /** @var int */
    private $id_customer;

    /** @var int */
    private $id_company;

    /** @var int */
    private $is_sandbox;

    /** @var string card token looking like a 32 characters hash : card_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx */
    private $id_card;

    /** @var string */
    private $last4;

    /** @var string */
    private $exp_month;

    /** @var string */
    private $exp_year;

    /** @var string|null */
    private $brand;

    /** @var string */
    private $country;

    /** @var string|null */
    private $metadata;

    /** @var array */
    private static $definition = [
        'table' => 'payplug_card',
        'primary' => 'id_payplug_card',
        'fields' => [
            'id_customer' => ['type' => 'integer', 'required' => true],
            'id_company' => ['type' => 'integer', 'required' => true],
            'is_sandbox' => ['type' => 'boolean', 'required' => true],
            'id_card' => ['type' => 'string', 'required' => true],
            'last4' => ['type' => 'string', 'required' => true],
            'exp_month' => ['type' => 'string', 'required' => true],
            'exp_year' => ['type' => 'string', 'required' => true],
            'brand' => ['type' => 'string', 'required' => false],
            'country' => ['type' => 'string', 'required' => true],
            'metadata' => ['type' => 'string', 'required' => false],
        ],
    ];

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
    public function getIdCustomer()
    {
        return $this->id_customer;
    }

    /**
     * @return int
     */
    public function getIdCompany()
    {
        return $this->id_company;
    }

    /**
     * @return int
     */
    public function getIsSandbox()
    {
        return $this->is_sandbox;
    }

    /**
     * @return string
     */
    public function getIdCard()
    {
        return $this->id_card;
    }

    /**
     * @return string
     */
    public function getLast4()
    {
        return $this->last4;
    }

    /**
     * @return string
     */
    public function getExpMonth()
    {
        return $this->exp_month;
    }

    /**
     * @return string
     */
    public function getExpYear()
    {
        return $this->exp_year;
    }

    /**
     * @return string|null
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return string|null
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @return array
     */
    public function getDefinition()
    {
        return self::$definition;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        if (!is_int($id)) {
            throw new BadParameterException('Invalid id, must be an integer.');
        }

        $this->id = $id;

        return $this;
    }

    /**
     * @param int $id_customer
     *
     * @return $this
     */
    public function setIdCustomer($id_customer)
    {
        if (!is_int($id_customer)) {
            throw new BadParameterException('Invalid id_customer, must be an integer.');
        }

        $this->id_customer = $id_customer;

        return $this;
    }

    /**
     * @param int $id_company
     *
     * @return $this
     */
    public function setIdCompany($id_company)
    {
        if (!is_int($id_company)) {
            throw new BadParameterException('Invalid id_company, must be an integer.');
        }

        $this->id_company = $id_company;

        return $this;
    }

    /**
     * @param bool $is_sandbox
     *
     * @return $this
     */
    public function setIsSandbox($is_sandbox)
    {
        if (!is_bool($is_sandbox)) {
            throw new BadParameterException('Invalid is_sandbox, must be an boolean.');
        }

        $this->is_sandbox = $is_sandbox;

        return $this;
    }

    /**
     * @param string $id_card
     *
     * @return $this
     */
    public function setIdCard($id_card)
    {
        if (!is_string($id_card) || !preg_match('/card_[a-z0-9]{32}/', $id_card)) {
            throw new BadParameterException('Invalid id_card, must be a string in the format card_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.');
        }

        $this->id_card = $id_card;

        return $this;
    }

    /**
     * @param string $last4
     *
     * @return $this
     */
    public function setLast4($last4)
    {
        if (!is_string($last4)) {
            throw new BadParameterException('Invalid last4, must be a string.');
        }

        $this->last4 = $last4;

        return $this;
    }

    /**
     * @param string $exp_month
     *
     * @return $this
     */
    public function setExpMonth($exp_month)
    {
        if (!is_string($exp_month)) {
            throw new BadParameterException('Invalid exp_month, must be a string.');
        }

        $this->exp_month = $exp_month;

        return $this;
    }

    /**
     * @param string $exp_year
     *
     * @return $this
     */
    public function setExpYear($exp_year)
    {
        if (!is_string($exp_year)) {
            throw new BadParameterException('Invalid exp_year, must be a string.');
        }

        $this->exp_year = $exp_year;

        return $this;
    }

    /**
     * @param string $brand
     *
     * @return $this
     */
    public function setBrand($brand = '')
    {
        if (!is_string($brand)) {
            throw new BadParameterException('Invalid brand, must be a string.');
        }

        $this->brand = $brand;

        return $this;
    }

    /**
     * @param string $country
     *
     * @return $this
     */
    public function setCountry($country)
    {
        if (!is_string($country)) {
            throw new BadParameterException('Invalid country, must be a string.');
        }

        $this->country = $country;

        return $this;
    }

    /**
     * @param string $metadata
     *
     * @return $this
     */
    public function setMetadata($metadata = '')
    {
        if (!is_string($metadata)) {
            throw new BadParameterException('Invalid metadata, must be a string.');
        }

        $this->metadata = $metadata;

        return $this;
    }
}
