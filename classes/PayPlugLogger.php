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

class PayPlugLogger extends ObjectModel
{
    /** @var string */
    public $process;

    /** @var text */
    public $content;

    /** @var datetime */
    public $date_add;

    /** @var datetime */
    public $date_upd;

    /** @var array */
    public static $definition = array(
        'table' => 'payplug_logger',
        'primary' => 'id_payplug_logger',
        'fields' => array(
            'process' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isCatalogName',
                'required' => true,
                'size' => 128
            ),
            'content' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'required' => true),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        )
    );

    /** @var int */
    private $limit_number = 500;

    /** @var string */
    private $limit_date = 'P1M';

    /**
     * @param string $process
     * @param int $id
     * @param int $id_lang
     * @return PayplugLock
     * @see ObjectModel::__construct()
     *
     */
    public function __construct($process = '', $id = null, $id_lang = null, $type = 'notification')
    {
        parent::__construct($id, $id_lang);
        $this->process = $process;
    }

    public function addLog($message, $level = 'info')
    {
        // get content
        $content = json_decode($this->content, true);
        if (!$content) {
            $content = [];
        }

        $date = $this->udate('Y-m-d H:i:s.u T');
        $entry = ['date' => $date, 'message' => $message, 'level' => $level];
        array_push($content, $entry);

        $this->content = json_encode($content);
        $this->save();

        return $this;
    }

    public function udate($format = 'u', $utimestamp = null)
    {
        if (is_null($utimestamp)) {
            $utimestamp = microtime(true);
        }

        $timestamp = floor($utimestamp);
        $milliseconds = round(($utimestamp - $timestamp) * 1000000);

        return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
    }

    protected function getLimit()
    {
        return [
            'number' => $this->limit_number,
            'date' => $this->limit_date,
        ];
    }

    public function flush($all = false){
        try {
            Db::getInstance()->getValue('SELECT 1 FROM `'._DB_PREFIX_.self::$definition['table'].'` LIMIT 1');
        } catch (Exception $exception) {
            return false;
        }

        if($all) {
            return Db::getInstance()->execute('TRUNCATE `'._DB_PREFIX_.self::$definition['table'].'`');
        }

        $limits = $this->getLimit();
        $date = new DateTime('now');
        $interval = new DateInterval($limits['date']);
        $date_limit = $date->sub($interval);

        $flag = true;

        // clean old log
        $sql = 'DELETE FROM `'._DB_PREFIX_.self::$definition['table'].'` WHERE `date_add` < "'.$date_limit->format('Y-m-d').'"';
        $flag = $flag && Db::getInstance()->execute($sql);

        // clean log beyong the limit
        $sql = 'SELECT `id_payplug_logger` FROM `'._DB_PREFIX_.self::$definition['table'].'` ORDER BY `id_payplug_logger` DESC LIMIT '.($limits['number'] - 1).',1';
        $last_logs_valid = Db::getInstance()->executeS($sql);

        // si there is no more log
        if(!$last_logs_valid) {
            return $flag;
        }

        $sql = 'DELETE FROM `'._DB_PREFIX_.self::$definition['table'].'` WHERE `id_payplug_logger` < ' . $last_logs_valid[0]['id_payplug_logger'];
        $flag = $flag && Db::getInstance()->execute($sql);

        return $flag;
    }
}

