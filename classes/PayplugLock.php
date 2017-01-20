<?php
/**
 * 2013 - 2016 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2016 PayPlug SAS
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class PayplugLock extends ObjectModel
{
    /** @var int */
    const MAX_CHECK_TIME = 5;

    /** @var array */
    public static $definition = array(
        'table'   => 'payplug_lock',
        'primary' => 'id_payplug_lock',
        'fields'  => array(
            'id_cart' => array('type' => 3, 'validate' => 'isInt', 'required' => true),
        )
    );

    /** @var int */
    public $id_payplug_lock;

    /** @var int */
    public $id_cart;

    /** @var datetime */
    public $date_add;

    /** @var datetime */
    public $date_upd;

    /** @var string */
    protected $table;

    /** @var string */
    protected $identifier;

    /** @var array */
    protected $fieldsRequired = array();

    /** @var array */
    protected $fieldsValidate = array();

    /** @var array */
    protected $fieldsValidateLang = array();

    /**
     * @see ObjectModel::__construct()
     *
     * @param int $id
     * @param int $id_lang
     * @return PayplugLock
     */
    public function __construct($id = null, $id_lang = null)
    {
        if (version_compare(_PS_VERSION_, 1.5, '<')) {
            $this->table      = self::$definition['table'];
            $this->identifier = self::$definition['primary'];
            foreach (self::$definition['fields'] as $key => $field) {
                if (isset($field['required']) && $field['required']) {
                    $this->fieldsRequired[] = $key;
                }

                $this->fieldsValidate[$key] = $field['validate'];
            }
        }
        parent::__construct($id, $id_lang);
    }

    /**
     * get fields
     *
     * @return array
     */
    public function getFields()
    {
        parent::validateFields();

        $fields = array();
        $fields['id_cart'] = (int)($this->id_cart);
        $fields['date_add'] = pSQL($this->date_add);
        $fields['date_upd'] = pSQL($this->date_upd);

        return $fields;
    }

    /**
     * Check
     *
     * @param  int $id_cart
     * @param  int $loop_time
     * @return void
     */
    public static function check($id_cart, $loop_time = 1)
    {
        $time = 0;

        while ((self::exists($id_cart)) && $time < PayplugLock::MAX_CHECK_TIME) {
            if (function_exists('usleep')) {
                usleep($loop_time * 1000000);
            } else {
                self::usleep($loop_time * 1000);
            }

            $time++;
        }
    }

    /**
     * Check if exists
     *
     * @param  int $id_cart
     * @return bool
     */
    public static function exists($id_cart)
    {
        $lock = self::getInstanceByCart((int)$id_cart);

        if ($lock === false) {
            return false;
        } else {
            return Validate::isLoadedObject($lock);
        }
    }

    /**
     * Set instance of PayplugLock
     *
     * @param  int $id_cart
     * @return PayplugLock
     */
    public static function getInstanceByCart($id_cart)
    {
        $query = 'SELECT `id_payplug_lock` 
				FROM `'._DB_PREFIX_.'payplug_lock`
				WHERE `id_cart` = '.(int)$id_cart.' ';

        $id = (int)Db::getInstance()->getValue($query);

        if ($id == 0) {
            return false;
        }

        return new PayplugLock($id);
    }

    /**
     * Create lock
     *
     * @param  int $id_cart
     * @return bool
     */
    public static function addLock($id_cart)
    {
        $lock = new PayplugLock();
        $lock->id_cart = (int)$id_cart;

        return $lock->save();
    }

    /**
     * Delete lock
     *
     * @param  int $id_cart
     * @return bool
     */
    public static function deleteLock($id_cart)
    {
        $lock = self::getInstanceByCart((int)$id_cart);

        if ($lock === false) {
            return false;
        } else {
            return $lock->delete();
        }
    }

    /**
     * Sleep time
     *
     * @param  int $seconds
     * @return void
     */
    private static function usleep($seconds)
    {
        $start = microtime();

        do {
            // Wait !
            $current = microtime();
        } while (($current - $start) < $seconds);
    }
}
