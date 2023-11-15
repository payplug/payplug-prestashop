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
    /** @var array */
    private $allowed_brand;

    /** @var string */
    private $brand;

    /** @var string */
    private $country;

    /** @var array */
    private $definition;

    /** @var string */
    private $exp_month;

    /** @var string */
    private $exp_year;

    /** @var array */
    private $fieldsRequired;

    /** @var array */
    private $fieldsSize;

    /** @var array */
    private $fieldsValidate;

    /** @var int */
    private $id;

    /** @var string card token looking like a 32 characters hash : card_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx */
    private $id_card;

    /** @var int */
    private $id_company;

    /** @var int */
    private $id_customer;

    /** @var int */
    private $identifier;

    /** @var bool */
    private $is_sandbox;

    /** @var string */
    private $last4;

    /** @var string */
    private $metadata;

    /** @var object */
    private $module;

    /** @var string */
    private $table;

    /**
     * @return array
     */
    public function getAllowedBrand()
    {
        return $this->allowed_brand;
    }

    /**
     * @return string
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
     * @return array
     */
    public function getDefinition()
    {
        return $this->definition;
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
     * @return array
     */
    public function getFieldsRequired()
    {
        return $this->fieldsRequired;
    }

    /**
     * @return array
     */
    public function getFieldsSize()
    {
        return $this->fieldsSize;
    }

    /**
     * @return array
     */
    public function getFieldsValidate()
    {
        return $this->fieldsValidate;
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
    public function getIdCard()
    {
        return $this->id_card;
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
    public function getIdCustomer()
    {
        return $this->id_customer;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return bool
     */
    public function getIsSandbox()
    {
        return $this->is_sandbox;
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
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @return object
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param $allowed_brand
     *
     * @return $this
     */
    public function setAllowedBrand($allowed_brand)
    {
        if (!is_array($allowed_brand)) {
            throw new BadParameterException('Invalid allowed brand, param $allowed_brand must be an array');
        }

        $this->allowed_brand = $allowed_brand;

        return $this;
    }

    /**
     * @param $brand
     *
     * @return $this
     */
    public function setBrand($brand)
    {
        if (!is_string($brand)) {
            throw new BadParameterException('Invalid brand, param $brand must be a string');
        }

        $this->brand = $brand;

        return $this;
    }

    /**
     * @param $country
     *
     * @return $this
     */
    public function setCountry($country)
    {
        if (!is_string($country)) {
            throw new BadParameterException('Invalid country, param $country must be a string');
        }

        $this->country = $country;

        return $this;
    }

    /**
     * @param $definition
     *
     * @return $this
     */
    public function setDefinition($definition)
    {
        if (!is_array($definition)) {
            throw new BadParameterException('Invalid definition, $definition must be an array');
        }

        $this->definition = $definition;

        return $this;
    }

    /**
     * @param $exp_month
     *
     * @return $this
     */
    public function setExpMonth($exp_month)
    {
        if (!is_string($exp_month)) {
            throw new BadParameterException('Invalid expiry month, param $exp_month must be a string');
        }

        $this->exp_month = $exp_month;

        return $this;
    }

    /**
     * @param $exp_year
     *
     * @return $this
     */
    public function setExpYear($exp_year)
    {
        if (!is_string($exp_year)) {
            throw new BadParameterException('Invalid expiry year, param $exp_year must be a string');
        }

        $this->exp_year = $exp_year;

        return $this;
    }

    /**
     * @param $fieldsRequired
     *
     * @return $this
     */
    public function setFieldsRequired($fieldsRequired)
    {
        if (!is_array($fieldsRequired)) {
            throw new BadParameterException('Invalid argument, $setFieldsRequired must be an array');
        }

        $this->fieldsRequired = $fieldsRequired;

        return $this;
    }

    /**
     * @param $fieldsSize
     *
     * @return $this
     */
    public function setFieldsSize($fieldsSize)
    {
        if (!is_array($fieldsSize)) {
            throw new BadParameterException('Invalid fieldsSize, param $fieldsSize must be an array');
        }
        $this->fieldsSize = $fieldsSize;

        return $this;
    }

    /**
     * @param $fieldsValidate
     *
     * @return $this
     */
    public function setFieldsValidate($fieldsValidate)
    {
        if (!is_array($fieldsValidate)) {
            throw new BadParameterException('Invalid fields validate, param $fieldsValidate must be an array');
        }
        $this->fieldsValidate = $fieldsValidate;

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
            throw new BadParameterException('Invalid id, param $id must be an integer');
        }

        $this->id = $id;

        return $this;
    }

    /**
     * @param $id_card
     *
     * @return $this
     */
    public function setIdCard($id_card)
    {
        if (!is_string($id_card) || !preg_match('/card_[a-z0-9]{32}/', $id_card)) {
            throw new BadParameterException('Invalid card token format, param $id_card must be a string looking like \'card_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx\'');
        }

        $this->id_card = $id_card;

        return $this;
    }

    /**
     * @param $id_company
     *
     * @return $this
     */
    public function setIdCompany($id_company)
    {
        if (!is_int($id_company)) {
            throw new BadParameterException('Invalid id, param $id_company must be an integer');
        }

        $this->id_company = $id_company;

        return $this;
    }

    /**
     * @param $id_customer
     *
     * @return $this
     */
    public function setIdCustomer($id_customer)
    {
        if (!is_int($id_customer)) {
            throw new BadParameterException('Invalid id, param $id_customer must be an integer');
        }

        $this->id_customer = $id_customer;

        return $this;
    }

    /**
     * @param $identifier
     *
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        if (!is_string($identifier)) {
            throw new BadParameterException('Invalid identifier, param $identifier must be a string');
        }
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @param $is_sandbox
     *
     * @return $this
     */
    public function setIsSandbox($is_sandbox)
    {
        if (!is_bool($is_sandbox)) {
            throw new BadParameterException('Param $is_sandbox must be a boolean');
        }

        $this->is_sandbox = $is_sandbox;

        return $this;
    }

    /**
     * @param $last4
     *
     * @return $this
     */
    public function setLast4($last4)
    {
        if (!is_string($last4)) {
            throw new BadParameterException('Invalid last4, param $last4 must be a string');
        }

        $this->last4 = $last4;

        return $this;
    }

    /**
     * @param $metadata
     *
     * @return $this
     */
    public function setMetadata($metadata)
    {
        if (!is_string($metadata)) {
            throw new BadParameterException('Invalid metadata, param $metadata must be a string');
        }

        $this->metadata = $metadata;

        return $this;
    }

    /**
     * @param $module
     *
     * @return $this
     */
    public function setModule($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * @param $table
     *
     * @return $this
     */
    public function setTable($table)
    {
        if (!is_string($table)) {
            throw new BadParameterException('Invalid table, param $table must be a string');
        }
        $this->table = $table;

        return $this;
    }
}
