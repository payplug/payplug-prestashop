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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @description The PayPlug Cache class is used to store and retrieve every Oney Simulation
 */
class PayPlugCache extends ObjectModel
{

    /** @var string */
    public $id_payplug_cache;

    /** @var string */
    public $cache_key;

    /** @var string */
    public $cache_value;

    /** @var datetime */
    public $date_add;

    /** @var datetime */
    public $date_upd;

    /** @var PayPlugLogger  */
    public $logger;

    /** @var array */
    public static $definition = array(
        'table' => 'payplug_cache',
        'primary' => 'id_payplug_cache',
        'fields' => array(
            'cache_key' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'cache_value' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        )
    );

    /**
     * @description Constructor of tf the class
     *
     * @param int $id
     * @param int $id_lang
     * @return PayplugCache
     * @see ObjectModel::__construct()
     */
    public function __construct($id = null, $id_lang = null)
    {
        parent::__construct($id, $id_lang);

        $this->logger = new PayPlugLogger('cache');
    }

    /**
     * @description Set the cache in the DB
     * Every Oney Simulation is stored
     *
     * @param string $cache_key
     * @param string $cache_value
     * @return boolean
     */
    public function setCache($cache_key, $cache_value)
    {
        // check if exists
        $cache = $this->getCacheByKey($cache_key);

        if (!Validate::isLoadedObject($cache)) {
            $cache->cache_key = $cache_key;
        }
        $cache->cache_value = Tools::jsonEncode($cache_value);
        return $cache->save();
    }

    /**
     * @description Get the Oney simulation (identified by id_payplug_cache in parameter), if it exists in the database.
     *
     * @param string $cache_key
     * @return object
     */
    public function getCacheByKey($cache_key)
    {
        $req_cache = 'SELECT `id_payplug_cache`  FROM `' . _DB_PREFIX_ . 'payplug_cache` WHERE `cache_key` = "' . (string)$cache_key . '"';
        $id_payplug_cache = Db::getInstance()->getValue($req_cache);
        return new PayPlugCache($id_payplug_cache);
    }

    /**
     * @description Remove ONE specific Oney simulation (identified by id_payplug_cache in parameter).
     *
     * @param $cache_key
     * @return boolean
     */
    public function deleteCacheByKey($cache_key)
    {
        $cache = $this->getCacheByKey($cache_key);
        if (Validate::isLoadedObject(!$cache)) {
            return false;
        }
        return $cache->delete();
    }

    /**
     * @description Flush all the cache.
     * Remove all Oney Simulation.
     *
     * @return boolean
     */
    public function flushCache()
    {
        $req_cache = '
            TRUNCATE
            ' . _DB_PREFIX_ . 'payplug_cache ';
        $res_cache = Db::getInstance()->execute($req_cache);
        if (!$res_cache) {
            $error_message = 'Error during flush the Oney Simulation DB cache [PayPlugCache.php]';
            $error_level = 'error';
            $this->logger->addLog($error_message,$error_level);
            return false;
        } else {
            return true;
        }
    }
}
