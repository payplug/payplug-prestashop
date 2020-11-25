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

namespace PayPlug\src\entities;

use PayPlug\src\exceptions\BadParameterException;

class CardEntity
{
    /** @var string card token looking like a 32 characters hash : card_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx */
    private $id_card;

    /** @var int */
    private $id_company;

    /** @var int */
    private $id_customer;

    /** @var int */
    private $id;

    /** @var bool */
    private $is_sandbox;

    /** @var string */
    private $last4;

    /** @var string */
    private $exp_month;

    /** @var string */
    private $exp_year;

    /** @var string */
    private $brand;

    /** @var array */
    private $allowed_brand;

    /** @var string */
    private $country;

    /** @var string */
    private $metadata;

    /** @var Module Payplug */
    private $module;

    private $definition;
    private $fieldsRequired;
    private $fieldsSize;
    private $fieldsValidate;
    private $table;
    private $identifier;

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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isSandbox()
    {
        return $this->is_sandbox;
    }

    /**
     * @param int $id_company
     * @return CardEntity
     * @throws BadParameterException
     */
    public function setIdCompany($id_company)
    {
        if (!is_int($id_company)) {
            throw (new BadParameterException('Invalid id, param $id_company must be an integer'));
        } else {
            $this->id_company = $id_company;
            return $this;
        }
    }

    /**
     * @param int $id_customer
     * @return CardEntity
     * @throws BadParameterException
     */
    public function setIdCustomer($id_customer)
    {
        if (!is_int($id_customer)) {
            throw (new BadParameterException('Invalid id, param $id_customer must be an integer'));
        } else {
            $this->id_customer = $id_customer;
            return $this;
        }
    }

    /**
     * @param int $id
     * @return CardEntity
     * @throws BadParameterException
     */
    public function setId($id)
    {
        if (!is_int($id)) {
            throw (new BadParameterException('Invalid id, param $id must be an integer'));
        } else {
            $this->id = $id;
            return $this;
        }
    }

    /**
     * @param bool $is_sandbox
     * @return CardEntity
     * @throws BadParameterException
     */
    public function setIsSandbox($is_sandbox)
    {
        if (!is_bool($is_sandbox)) {
            throw (new BadParameterException('Param $id_card must be a boolean'));
        } else {
            $this->is_sandbox = $is_sandbox;
            return $this;
        }
    }

    /**
     * @param string $id_card card token looking like a 32 characters hash : card_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
     * @return CardEntity
     */
    public function setIdCard($id_card)
    {
        if (!is_string($id_card) || !preg_match('/card_[a-z0-9]{32}/', $id_card)) {
            throw (new BadParameterException('Invalid card token format, param $id_card must be a string looking like \'card_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx\''));
        } else {
            $this->id_card = $id_card;
            return $this;
        }
    }

    /**
     * @return string
     */
    public function getLast4()
    {
        return $this->last4;
    }

    /**
     * @param string $last4
     * @return CardEntity
     */
    public function setLast4($last4)
    {
        $this->last4 = $last4;
        return $this;
    }

    /**
     * @return string
     */
    public function getExpMonth()
    {
        return $this->exp_month;
    }

    /**
     * @param string $exp_month
     * @return CardEntity
     */
    public function setExpMonth($exp_month)
    {
        $this->exp_month = $exp_month;
        return $this;
    }

    /**
     * @return string
     */
    public function getExpYear()
    {
        return $this->exp_year;
    }

    /**
     * @param string $exp_year
     * @return CardEntity
     */
    public function setExpYear($exp_year)
    {
        $this->exp_year = $exp_year;
        return $this;
    }

    /**
     * @return string
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     * @return CardEntity
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
        return $this;
    }

    /**
     * @return array
     */
    public function getAllowedBrand()
    {
        return $this->allowed_brand;
    }

    /**
     * @param array $allowed_brand
     * @return CardEntity
     */
    public function setAllowedBrand($allowed_brand)
    {
        $this->allowed_brand = $allowed_brand;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     * @return CardEntity
     */
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return string
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param string $metadata
     * @return CardEntity
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @return Module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param Module $module
     * @return CardEntity
     */
    public function setModule($module)
    {
        $this->module = $module;
        return $this;
    }

    /**
     * @return array
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param array $definition
     * @return CardEntity
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;
        return $this;
    }

    /**
     * @return array
     */
    public function getFieldsRequired()
    {
        return $this->fieldsRequired;
    }

    /**
     * @param array $fieldsRequired
     * @return CardEntity
     */
    public function setFieldsRequired($fieldsRequired)
    {
        $this->fieldsRequired = $fieldsRequired;
        return $this;
    }

    /**
     * @return array
     */
    public function getFieldsSize()
    {
        return $this->fieldsSize;
    }

    /**
     * @param array $fieldsSize
     * @return CardEntity
     */
    public function setFieldsSize($fieldsSize)
    {
        $this->fieldsSize = $fieldsSize;
        return $this;
    }

    /**
     * @return array
     */
    public function getFieldsValidate()
    {
        return $this->fieldsValidate;
    }

    /**
     * @param array $fieldsValidate
     * @return CardEntity
     */
    public function setFieldsValidate($fieldsValidate)
    {
        $this->fieldsValidate = $fieldsValidate;
        return $this;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param string $table
     * @return CardEntity
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @return CardEntity
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }
    
    
}