<?php
/**
 * 2013 - 2021 PayPlug SAS
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
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\entities;

use PayPlug\src\exceptions\BadParameterException;

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
    private $definition;

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
        * @var string $table
        */
    private $table;

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
    public function setProcess($process)
    {
        if (!is_string($process)) {
            throw (new BadParameterException('Invalid setProcess, param $process must be a string'));
        } else {
            $this->process = $process;
            return $this;
        }
    }

    /**
     * @return text
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return LoggerEntity
     */
    public function setContent($content)
    {
        if (!is_string($content)) {
            throw (new BadParameterException('Invalid content, param $content must be a string'));
        } else {
            $this->content = $content;
            return $this;
        }
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
    public function setDateAdd($date_add)
    {
        if (!is_string($date_add) || !preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date_add)) {
            throw (
            new BadParameterException(
                'Invalid datetime format, param $date_add must be a string looking like \'yyyy-mm-dd hh:mm:ss\''
            )
            );
        } else {
            $this->date_add = $date_add;
            return $this;
        }
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
        if (!is_string($date_upd) || !preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date_upd)) {
            throw (
            new BadParameterException(
                'Invalid datetime format, param $date_upd must be a string looking like \'yyyy-mm-dd hh:mm:ss\''
            )
            );
        } else {
            $this->date_upd = $date_upd;
            return $this;
        }
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
     * @return LoggerEntity
     */
    public function setDefinition($definition)
    {
        if (!is_array($definition)) {
            throw (new BadParameterException('Invalid id, param $definition must be an array'));
        } else {
            $this->definition = $definition;
            return $this;
        }
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
    public function setLimitNumber($limit_number)
    {
        if (!is_int($limit_number)) {
            throw (new BadParameterException('Invalid id, param $id must be an integer'));
        } else {
            $this->limit_number = $limit_number;
            return $this;
        }
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
    public function setLimitDate($limitDate)
    {
        if (!is_string($limitDate)) {
            throw (new BadParameterException('Invalid id, param $id must be an integer'));
        } else {
            $this->limitDate = $limitDate;
            return $this;
        }
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
    public function setId($id)
    {
        if (!is_string($id)) {
            throw (new BadParameterException('Invalid id, param $id must be an integer'));
        } else {
            $this->id = $id;
            return $this;
        }
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return LoggerEntity
     */
    public function setType($type)
    {
        if (!is_string($type)) {
            throw (new BadParameterException('Invalid id, param $id must be an integer'));
        } else {
            $this->type = $type;
            return $this;
        }
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
     * @return LoggerEntity
     */
    public function setTable($table)
    {
        if (!is_string($table)) {
            throw (new BadParameterException('Invalid id, param $id must be an integer'));
        } else {
            $this->table= $table;
            return $this;
        }
    }
}
