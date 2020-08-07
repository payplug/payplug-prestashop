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

require_once(_PS_MODULE_DIR_ . 'payplug/classes/PayplugBackward.php');

class PayPlugCard extends ObjectModel
{
    /** @var int */
    public $id;

    /** @var int */
    public $id_customer;

    /** @var int */
    public $id_company;

    /** @var bool */
    public $is_sandbox;

    /** @var string */
    public $id_card;

    /** @var string */
    public $last4;

    /** @var string */
    public $exp_month;

    /** @var string */
    public $exp_year;

    /** @var string */
    public $brand;

    /** @var array */
    public $allowed_brand = array(
        'mastercard',
        'visa'
    );

    /** @var string */
    public $country;

    /** @var string */
    public $metadata;

    /** @var Module Payplug */
    protected $module = null;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array();
    public $fieldsRequired = array();
    public $fieldsSize = array();
    public $fieldsValidate = array();
    public $table = '';
    public $identifier = '';

    /**
     * PayPlugCard constructor.
     * @param null $id
     * @param null $id_lang
     * @param null $id_shop
     */
    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        $definition = array(
            'table' => 'payplug_card',
            'primary' => 'id_payplug_card',
            'fields' => array(
                'id_customer' => array('type' => 'int', 'validate' => 'isUnsignedId', 'required' => true),
                'id_company' => array('type' => 'int', 'validate' => 'isUnsignedId', 'required' => true),
                'is_sandbox' => array('type' => 'bool', 'validate' => 'isBool'),
                'id_card' => array('type' => 'string', 'validate' => 'isCleanHtml', 'required' => true),
                'last4' => array('type' => 'string', 'validate' => 'isCleanHtml', 'size' => 4, 'required' => true),
                'exp_month' => array('type' => 'string', 'validate' => 'isCleanHtml', 'size' => 4, 'required' => true),
                'exp_year' => array('type' => 'string', 'validate' => 'isCleanHtml', 'size' => 4, 'required' => true),
                'brand' => array('type' => 'string', 'validate' => 'isCleanHtml'),
                'country' => array(
                    'type' => 'string',
                    'validate' => 'isLanguageIsoCode',
                    'size' => 3,
                    'required' => false
                ),
                'metadata' => array('type' => 'string', 'validate' => 'isCleanHtml'),
            )
        );

        PayplugBackward::defineObjectModel($this, $definition);

        $this->module = new Payplug();

        $this->id_company = $this->module->getConfiguration('PAYPLUG_COMPANY_ID');
        $this->is_sandbox = $this->module->getConfiguration('PAYPLUG_SANDBOX_MODE');

        return parent::__construct($id, $id_lang, $id_shop);
    }


    /**
     * Get database fields for compatibility 1.4
     * @return mixed
     */
    public function getFields()
    {
        parent::validateFields();

        if (isset($this->id)) {
            $fields['id_payplug_card'] = (int)($this->id);
        }
        $fields['id_customer'] = is_null($this->id_customer) ? 0 : (int)($this->id_customer);
        $fields['id_company'] = is_null($this->id_company) ? 0 : (int)($this->id_company);
        $fields['is_sandbox'] = (int)$this->is_sandbox;
        $fields['id_card'] = pSQL($this->id_card);
        $fields['last4'] = pSQL($this->last4);
        $fields['exp_month'] = pSQL($this->exp_month);
        $fields['exp_year'] = pSQL($this->exp_year);
        $fields['brand'] = pSQL($this->brand);
        $fields['country'] = pSQL($this->country);
        $fields['metadata'] = pSQL($this->metadata);

        return $fields;
    }

    /**
     * @param bool $auto_date
     * @param bool $null_values
     * @return bool
     */
    public function save($auto_date = true, $null_values = false)
    {
        $this->brand = $this->validateBrandCard();
        $this->country = $this->validateCountry();

        if ($id_playplug_card = $this->exists()) {
            $this->id = $id_playplug_card;
        }

        return parent::save($auto_date, $null_values);
    }

    /**
     * Check if playplug card exits in db, then return the identifier
     * @return integer
     */
    public function exists(){
        $sql = 'SELECT `id_payplug_card` 
                FROM `'._DB_PREFIX_.'payplug_card`
                WHERE `id_company` = '.(int)$this->id_company.' 
                AND `id_customer` = '.(int)$this->id_customer.' 
                AND `id_card` = "'.(string)$this->id_card.'"';
        return Db::getInstance()->getValue($sql);
    }

    /**
     * @return bool
     */
    public function delete()
    {
        try {
            $delete = \Payplug\Card::delete($this->id_card);
            if ($delete) {
                return parent::delete();
            }
        } catch (Exception $e) {
            //@todo: add log
            if ($e->getCode() == '404') { // resource cant be found
                return parent::delete();
            }
            return false;
        }
    }

    /**
     * Delete all cards with customer in option
     *
     * @param int $id_customer
     * @return bool
     */
    public static function deleteAll($id_customer = false)
    {
        $sql = 'SELECT `id_payplug_card` FROM `' . _DB_PREFIX_ . 'payplug_card`'
            . ($id_customer ? ' WHERE `id_customer` = ' . (int)$id_customer : '');
        $cards = Db::getInstance()->executeS($sql);

        if ($cards) {
            foreach ($cards as $card) {
                $payplug_card = new PayPlugCard($card['id_payplug_card']);
                if (!$payplug_card->delete()) {
                    //@todo: log deletion failure
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if a card can be use
     *
     * @param int $month
     * @param int $year
     * @return array OR bool
     */
    public static function isValidExpiration($month, $year)
    {
        if ($month == null || $year == null) {
            return false;
        }

        if ($year < (int)date('Y') || ($year == (int)date('Y') && $month < (int)date('m'))) {
            return false;
        }

        return true;
    }

    /**
     * Check if brand card can be use
     *
     * @return bool
     */
    public function isValidBrand()
    {
        return in_array(Tools::strtolower($this->brand), $this->allowed_brand);
    }

    /**
     * @return string
     */
    public function validateBrandCard()
    {
        if (!$this->isValidBrand()) {
            $this->brand = 'none';
        }
        return $this->brand;
    }

    /**
     * @return string
     */
    public function validateCountry()
    {
        return $this->country === null ? '' : $this->country;
    }

    /**
     * Get collection of cards fort a given customer
     *
     * @param Customer $customer
     * @param bool $active_only
     * @return array OR bool
     */
    public function getByCustomer($customer, $active_only = false)
    {
        if (!is_object($customer)) {
            $customer = new Customer((int)$customer);
        }

        if (!Validate::isLoadedObject($customer)) {
            return false;
        }

        $sql = 'SELECT *
                FROM `' . _DB_PREFIX_ . 'payplug_card`
                WHERE `id_customer` = ' . (int)$customer->id . '
                AND `id_company` = ' . (int)$this->id_company . '
                AND `is_sandbox` = ' . (int)$this->is_sandbox;

        $cards = Db::getInstance()->executeS($sql);

        // unset secret datas
        foreach ($cards as $key => &$card) {
            if (!PayPlugCard::isValidExpiration((int)$card['exp_month'], (int)$card['exp_year'])) {
                $card['expired'] = true;
                if ($active_only) {
                    unset($cards[$key]);
                    continue;
                }
            } else {
                $card['expired'] = false;
            }
            $card['expiry_date'] = date('m / y', mktime(0, 0, 0, (int)$card['exp_month'], 1, (int)$card['exp_year']));

            unset($card['is_sandbox']);
            unset($card['id_card']);
        }
        return $cards;
    }

    /**
     * @param $customer
     * @param bool $active_only
     * @return array|bool
     */
    public static function getCardsByCustomer($customer, $active_only = false)
    {
        if (!is_object($customer)) {
            $customer = new Customer((int)$customer);
        }

        if (!Validate::isLoadedObject($customer)) {
            return false;
        }

        $payplug_card = new PayPlugCard();
        return $payplug_card->getByCustomer($customer, $active_only);
    }
}
