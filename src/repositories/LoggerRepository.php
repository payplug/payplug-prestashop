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

namespace PayPlug\src\repositories;

use PayPlug\src\application\dependencies\BaseClass;
use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\LoggerEntity;

class LoggerRepository extends BaseClass
{
    private $dependencies;
    private $loggerEntity;
    private $validators;

    public function __construct($dependencies)
    {
        $this->loggerEntity = new loggerEntity();
        $this->dependencies = $dependencies;
        $this->validators = $this->dependencies->getValidators();
        $this->setStdParams();
    }

    public static function factory()
    {
        return new LoggerRepository();
    }

    /**
     * @description Hydrate standard entities
     */
    public function setStdParams()
    {
        $this->loggerEntity
            ->setTable($this->dependencies->name . '_logger')
            ->setLimitNumber((int) 4000)
            ->setLimitDate('P1M')
            ->setDefinition([
                'table' => $this->loggerEntity->getTable(),
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
                    'date_upd' => ['type' => 5, 'validate' => 'isDate'],
                ],
            ])
        ;
    }

    /**
     * @description Used to set $process and $type since other classes
     *
     * @param string $process
     */
    public function setProcess($process = '')
    {
        $validate = $this->validators['logger']->isAllowedProcess($process);
        if (!$validate['result']) {
            throw (new BadParameterException($validate['message']));
        }

        $this->loggerEntity->setProcess($process);
    }

    /**
     * @description Add message to PayPlug Logger
     *
     * @param $message
     * @param string $level
     *
     * @return $this
     */
    public function addLog($message, $level = 'info')
    {
        $validate = $this->validators['logger']->isContent($message);
        if (!$validate['result']) {
            throw (new BadParameterException($validate['message']));
        }

        // get content
        $content = json_decode($this->loggerEntity->getContent(), true);
        if (!$content) {
            $content = [];
        }

        $debug = '';
        $debugBacktrace = debug_backtrace();
        if (isset($debugBacktrace)) {
            $debug = reset($debugBacktrace);
        }

        $this->loggerEntity->setDateAdd($this->udate('Y-m-d H:i:s')); // without .u T
        $entry = [
            'date' => $this->udate('Y-m-d H:i:s.u T'),
            'line' => $debug['line'],
            'message' => $message,
            'level' => $level,
        ];
        array_push($content, $entry);

        $this->loggerEntity->setContent(json_encode($content));

        $this->save();

        return $this;
    }

    /**
     * @description Check if log exist to update it or create a new one
     *
     * @return bool
     */
    public function save()
    {
        if ((int) $this->loggerEntity->getId() > 0) {
            $this->loggerEntity->setDateUpd($this->udate('Y-m-d H:i:s'));

            return $this->updateLog();
        }

        $this->addToDb();
    }

    /**
     * @description If new log, add it to db
     *
     * @return bool
     */
    public function addToDb()
    {
        $parameters = [
            'process' => $this->loggerEntity->getProcess(),
            'content' => $this->loggerEntity->getContent(),
            'date_add' => $this->loggerEntity->getDateAdd(),
            'date_upd' => $this->loggerEntity->getDateAdd(),
        ];

        $id_logger = $this->dependencies
            ->getPlugin()
            ->getLoggerRepository()
            ->createLog($parameters);

        if (!$id_logger) {
            return false;
        }

        $this->loggerEntity->setId($id_logger);
    }

    /**
     * @description Add message in existing log
     *
     * @return bool
     */
    public function updateLog()
    {
        $parameters = [
            'process' => $this->loggerEntity->getProcess(),
            'content' => $this->loggerEntity->getContent(),
            'date_upd' => $this->loggerEntity->getDateUpd(),
        ];

        return $this->dependencies
            ->getPlugin()
            ->getLoggerRepository()
            ->updateLog((int) $this->loggerEntity->getId(), $parameters);
    }

    /**
     * @description Format date to help for more precisions
     *
     * @param string $format
     * @param null   $utimestamp
     *
     * @return false|string
     */
    public function udate($format = 'u', $utimestamp = null)
    {
        if (is_null($utimestamp)) {
            $utimestamp = microtime(true);
        }

        $timestamp = floor($utimestamp);
        $milliseconds = round(($utimestamp - $timestamp) * 1000000);

        return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
    }

    /**
     * @description Flush PayPlug Logger
     *
     * @param bool $all
     *
     * @return bool
     */
    public function flush($all = false)
    {
        try {
            $logger = $this->loggerEntity;
            $query = $this->dependencies
                ->getPlugin()
                ->getQueryRepository();
            $query
                ->select()
                ->fields('*')
                ->from(_DB_PREFIX_ . $logger->getTable())
            ;
        } catch (Exception $exception) {
            return false;
        }

        if ($all) {
            $query
                ->truncate()
                ->table(_DB_PREFIX_ . $logger->getTable())
            ;

            if (!$query->build()) {
                return false;
            }

            return true;
        }

        $limits = $this->getLimit();
        $date = new DateTime('now');
        $interval = new DateInterval($limits['date']);
        $date_limit = $date->sub($interval);

        $flag = true;

        // clean old log
        $query
            ->delete()
            ->from(_DB_PREFIX_ . $logger->getTable())
            ->where('`date_add` < ' . $query->escape($date_limit->format('Y-m-d')))
        ;

        if (!$query->build()) {
            $flag = false;
        }

        // clean log beyong the limit
        $last_logs_valid =
            $query
                ->select()
                ->fields('`id_payplug_logger`')
                ->from(_DB_PREFIX_ . $logger->getTable())
                ->orderBy('`id_payplug_logger` DESC')
                ->limit(($limits['number'] - 1), 1)
            ;

        if (!$last_logs_valid || !$query->build()) {
            $flag = false;
        }

        $query
            ->delete()
            ->from(_DB_PREFIX_ . $logger->getTable())
            ->where('`id_payplug_logger` < ' . (int) $last_logs_valid[0]['id_payplug_logger'])
        ;

        if (!$query->build()) {
            $flag = false;
        }

        return $flag;
    }

    /**
     * @description Return the defined date and max elements limits
     *
     * @return array
     */
    protected function getLimit()
    {
        return [
            'number' => $this->limit_number,
            'date' => $this->limit_date,
        ];
    }
}
