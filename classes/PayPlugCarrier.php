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
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2020 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

/**
 * @description
 * PayPlugCarrier : This class associate delivery type and delivery time to existing Carrier
 * It's necessary for Oney
 */

class PayPlugCarrier extends ObjectModel
{
    /** @const int Default delivery delay value in days for new carrier */
    const CARRIER_DEFAULT_DELAY = 0;

    /** @const string Default delivery type value for new carrier */
    const CARRIER_DEFAULT_DELIVERY_TYPE = 'storepickup';

    /** @var int Carrier id */
    public $id_carrier;

    /** @var int Number of days for delivery */
    public $delay;

    /** @var string Delivery method storepickup|networkpickup|travelpickup|carrier|edelivery */
    public $delivery_type;

    /** @var string Object creation date */
    public $date_add;

    /** @var string Object last modification date */
    public $date_upd;

    /** @var string Carrier name */
    public $name;

    public static $definition = array(
        'table' => 'payplug_carrier',
        'primary' => 'id_payplug_carrier',
        'fields' => array(
            'id_carrier' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'delay' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true, 'size' => 3),
            'delivery_type' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isCleanHtml',
                'size' => 100
            ),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        )
    );

    /**
     * @description
     * Get Carriers from Prestashop who are not "deleted" for a given language
     *
     * @param int $id_lang ID of a language
     * @param boolean $is_active
     * @return array of PayPlugCarrier
     */
    public static function getCarriers($id_lang, $is_active = true)
    {
        $sql = 'SELECT pc.`id_payplug_carrier`, c.`name`, c.`id_carrier`
                FROM `'._DB_PREFIX_.'carrier` c
                LEFT JOIN `'._DB_PREFIX_.self::$definition['table'] . '` pc ON (pc.id_carrier = c.id_carrier)
                WHERE c.`deleted` = 0' . ($is_active ? ' AND c.`active` = 1' : '');
        $carriers = Db::getInstance()->executeS($sql);

        $active_carriers = [];
        if (!empty($carriers)) {
            foreach ($carriers as $carrier) {
                $c = new PayPlugCarrier($carrier['id_payplug_carrier']);

                if (!Validate::isLoadedObject($c)) {
                    $c->id_carrier = $carrier['id_carrier'];
                    $c->delay = PayPlugCarrier::CARRIER_DEFAULT_DELAY;
                    $c->delivery_type = PayPlugCarrier::CARRIER_DEFAULT_DELIVERY_TYPE;
                }

                if ($carrier['name'] !== '0') {
                    $c->name = $carrier['name'];
                } else {
                    $c->name = Carrier::getCarrierNameFromShopName();
                }
                $active_carriers[$c->id_carrier] = $c;
            }
        }
        return $active_carriers;
    }

    /**
     * @description
     * Get all PayPlugCarrier registered in database
     *
     * @return array of PayPlugCarrier
     */
    public static function getAll()
    {
        $carriers = array();
        $req_payplug_carriers = '
            SELECT pc.`id_payplug_carrier`, c.`name` 
            FROM `'._DB_PREFIX_.self::$definition['table'].'` pc
            LEFT JOIN `'._DB_PREFIX_.'carrier` c ON (c.id_carrier = pc.id_carrier)
            ORDER BY pc.`id_payplug_carrier` ASC';
        $res_payplug_carriers = Db::getInstance()->executeS($req_payplug_carriers);
        if ($res_payplug_carriers) {
            foreach ($res_payplug_carriers as $carrier) {
                $c = new PayPlugCarrier($carrier['id_payplug_carrier']);
                if ($carrier['name'] !== '0') {
                    $c->name = $carrier['name'];
                } else {
                    $c->name = Carrier::getCarrierNameFromShopName();
                }
                $carriers[] = $c;
            }
        }
        return $carriers;
    }

    /**
     * @description
     * Get PayPlugCarrier for given id_carrier
     *
     * @param int $id_carrier ID of a Prestashop Carrier
     * @return PayPlugCarrier
     */
    public function getByIdCarrier($id_carrier)
    {
        $id_payplug_carrier = Db::getInstance()->getValue(
            'SELECT `' . self::$definition['primary']
            . '` FROM `'._DB_PREFIX_ . self::$definition['table']
            .'` WHERE `id_carrier` = ' . (int)$id_carrier);
        if($id_payplug_carrier) {
            return new PayPlugCarrier($id_payplug_carrier);
        }

        return $this;
    }

    /**
     * @description
     * Get name from the corresponding Prestashop Carrier
     *
     * @return string Carrier name
     */
    public function getName()
    {
        $carrier = new Carrier($this->id_carrier);
        return $carrier->name;
    }

    /**
     * @description
     * Get PayPlugCarrier for given id_carrier : static version
     *
     * @param int $id_carrier ID of a Prestashop Carrier
     * @return PayPlugCarrier
     */
    public static function getPayPlugCarrierByIdCarrier($id_carrier)
    {
        $sql = 'SELECT pc.`id_payplug_carrier`
                FROM `'._DB_PREFIX_.self::$definition['table'].'` pc
                WHERE pc.`id_carrier` = '.(int)$id_carrier;
        $id_payplug_carrier = Db::getInstance()->getValue($sql);
        return new PayPlugCarrier((int)$id_payplug_carrier);
    }

    /**
     * @description
     * Automatically populate a PayPlugCarrier with basic data from a given Carrier
     *
     * @param Carrier $carrier Object Carrier
     * @return void
     */
    public function populateFromCarrier($carrier)
    {
        if (!Validate::isLoadedObject($carrier)) {
            $carrier = new Carrier((int)$carrier);
        }

        $this->id_carrier = $carrier->id;
        $this->delay = PayPlugCarrier::CARRIER_DEFAULT_DELAY;
        $this->delivery_type = PayPlugCarrier::CARRIER_DEFAULT_DELIVERY_TYPE;
    }
}
