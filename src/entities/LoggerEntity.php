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

if (!defined('_PS_VERSION_')) {
    exit;
}

class LoggerEntity
{
    /**
     * @var string $process
     */
    private $process;

    /**
     * @var text $content
     */
    private $content;

    /**
     * @var datetime $dateAdd
     */
    private $date_add;

    /**
     * @var datetime $dateUpd
     */
    private $date_upd;

    /**
     * @var array $definition
     */
    private static $definition;

    /**
     * @var int $limitNumber
     */
    private $limit_number;

    /**
     * @var string $limitDate
     */
    private $limitDate;

    /**
     * @var string $id
     */
    private $id;

    /**
     * @var string $type
     */
    private $type;

    /**
     * @return string
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @param string $process
     * @return LoggerEntity
     */
    public function setProcess(string $process)
    {
        $this->process = $process;
        return $this;
    }

    /**
     * @return text
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param $content
     * @return LoggerEntity
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return datetime
     */
    public function getDateAdd()
    {
        return $this->date_add;
    }

    /**
     * @param string $date_add
     * @return LoggerEntity
     */
    public function setDateAdd(string $date_add)
    {
        $this->date_add = $date_add;
        return $this;
    }

    /**
     * @return datetime
     */
    public function getDateUpd()
    {
        return $this->date_upd;
    }

    /**
     * @param $date_upd
     * @return LoggerEntity
     */
    public function setDateUpd($date_upd)
    {
        $this->date_upd = $date_upd;
        return $this;
    }

    /**
     * @return array
     */
    public static function getDefinition()
    {
        return self::$definition;
    }

    /**
     * @param array $definition
     */
    public static function setDefinition(array $definition)
    {
        self::$definition = $definition;
    }

    /**
     * @return int
     */
    public function getLimitNumber()
    {
        return $this->limit_number;
    }

    /**
     * @param int $limit_number
     * @return LoggerEntity
     */
    public function setLimitNumber(int $limit_number)
    {
        $this->limit_number = $limit_number;
        return $this;
    }

    /**
     * @return string
     */
    public function getLimitDate()
    {
        return $this->limitDate;
    }

    /**
     * @param string $limitDate
     * @return LoggerEntity
     */
    public function setLimitDate(string $limitDate)
    {
        $this->limitDate = $limitDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return LoggerEntity
     */
    public function setId(string $id): LoggerEntity
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return LoggerEntity
     */
    public function setType(string $type): LoggerEntity
    {
        $this->type = $type;
        return $this;
    }
}