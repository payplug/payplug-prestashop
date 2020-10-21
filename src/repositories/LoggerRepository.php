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

namespace PayPlug\src\repositories;

use PayPlug\src\entities\LoggerEntity;
use PayPlug\src\specific\ConfigurationSpecific;
use PayPlug\src\specific\DatabaseSpecific;

class LoggerRepository
{
    /**
     * @var object $LoggerEntity
     */
    private $loggerEntity;
    private $database;
    private $configuration;

    public function __construct()
    {
        $this->loggerEntity = new loggerEntity();
        $this->database = new DatabaseSpecific();
        $this->configuration = new ConfigurationSpecific();
        $this->setStdParams();
    }

    public function setStdParams()
    {
        $this->loggerEntity
            ->setLimitNumber((int)4000)
            ->setLimitDate('P1M')
            ->setDefinition(
            [
                'table' => 'payplug_logger',
                'primary' => 'id_payplug_logger',
                'fields' => [
                    /*
                     * Different types,
                     * according to modules/gamification/tests/mocks/ObjectModel.php :
                     * TYPE_INT = 1;
                     * TYPE_BOOL = 2;
                     * TYPE_STRING = 3;
                     * TYPE_FLOAT = 4;
                     * TYPE_DATE = 5;
                     * TYPE_HTML = 6;
                     * TYPE_NOTHING = 7;
                     * TYPE_SQL = 8;
                     */
                    'process' => ['type' => 3, 'validate' => 'isCatalogName', 'required' => true, 'size' => 128],
                    'content' => ['type' => 6, 'validate' => 'isCleanHtml', 'required' => true],
                    'date_add' => ['type' => 5, 'validate' => 'isDate'],
                    'date_upd' => ['type' => 5, 'validate' => 'isDate']
                ]
            ]);
    }


    /**
     * Used to set $process and $type since other classes
     */
    public function setParams($params)
    {
        $this->loggerEntity
            ->setProcess('')
            ->setType('notification')
        ;

        if (isset($params['process']))  { $this->loggerEntity->setProcess($params['process']);  }
        if (isset($params['type']))     { $this->loggerEntity->setType($params['type']);        }
    }

    public function addLog($message, $level = 'info')
    {
        // get content
        $content = json_decode($this->loggerEntity->getContent(), true);
        if (!$content) {
            $content = [];
        }

        $this->loggerEntity->setDateAdd($this->udate('Y-m-d H:i:s.u T'));
        $entry = ['date' => $this->loggerEntity->getDateAdd(), 'message' => $message, 'level' => $level];
        array_push($content, $entry);

        $this->loggerEntity->setContent(json_encode($content));

        $this->save();

        return $this;
    }

    public function save()
    {
        if ((int)$this->loggerEntity->getId() > 0) {
            $this->loggerEntity->setDateUpd($this->udate('Y-m-d H:i:s.u T'));
            return $this->updateLog();
        }

        $this->addToDb();
    }

    public function addToDb()
    {
        $req_add_log = '
                INSERT INTO ' . _DB_PREFIX_ . 'payplug_logger (process, content, date_add, date_upd)
                VALUES (\'' . pSQL($this->loggerEntity->getProcess()) . '\', 
                        \'' . pSQL($this->loggerEntity->getContent()) . '\', 
                        \'' . pSQL($this->loggerEntity->getDateAdd()) . '\', 
                        \'' . pSQL($this->loggerEntity->getDateAdd()) . '\')';
        $res_add_log = $this->database->query('execute',$req_add_log);

        $this->loggerEntity->setId($this->database->query('Insert_ID'));

        if (!$res_add_log) {
            return false;
        }
    }

    public function updateLog()
    {
        $req_upd_log = '
                UPDATE ' . _DB_PREFIX_ . 'payplug_logger log  
                SET log.process = \'' . pSQL($this->loggerEntity->getProcess()) . '\', 
                    log.content = \'' . pSQL($this->loggerEntity->getContent()) . '\', 
                    log.date_upd = \'' . pSQL($this->loggerEntity->getDateUpd()) . '\'
                WHERE log.id_payplug_logger = ' . (int)$this->loggerEntity->getId();
        $res_upd_log = $this->database->query('execute',$req_upd_log);

        if (!$res_upd_log) {
            return false;
        }

        return $res_upd_log;
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

    public function flush($all = false) {
        try {
            $this->database->query('getValue','SELECT * FROM `'._DB_PREFIX_.'payplug_logger`');
        } catch (Exception $exception) {
            return false;
        }

        if($all) {
            return $this->database->query('execute','TRUNCATE `'._DB_PREFIX_.'payplug_logger`');
        }

        $limits = $this->getLimit();
        $date = new DateTime('now');
        $interval = new DateInterval($limits['date']);
        $date_limit = $date->sub($interval);

        $flag = true;

        // clean old log
        $sql = 'DELETE FROM `'._DB_PREFIX_.'payplug_logger` WHERE `date_add` < "'.$date_limit->format('Y-m-d').'"';
        $flag = $flag && $this->database->query('execute',$sql);

        // clean log beyong the limit
        $sql = 'SELECT `id_payplug_logger` FROM `'._DB_PREFIX_.'payplug_logger` ORDER BY `id_payplug_logger` DESC LIMIT '.($limits['number'] - 1).',1';
        $last_logs_valid = $this->database->query('executeS',$sql);

        // si there is no more log
        if(!$last_logs_valid) {
            return $flag;
        }

        $sql = 'DELETE FROM `'._DB_PREFIX_.'payplug_logger` WHERE `id_payplug_logger` < ' . $last_logs_valid[0]['id_payplug_logger'];
        $flag = $flag && $this->database->query('execute',$sql);

        return $flag;
    }
}