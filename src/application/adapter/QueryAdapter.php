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

namespace PayPlug\src\application\adapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\src\interfaces\QueryInterface;

class QueryAdapter implements QueryInterface
{
    private $db;

    public function __construct()
    {
        try {
            $this->db = \Db::getInstance();
        } catch (\Exception $e) {
            var_dump($e);
        }
    }

    /**
     * @description Called from src/repositories/QueryRepository.php
     *
     * @param $SQLRequest
     *
     * @return mixed
     */
    public function query($SQLRequest)
    {
        try {
            $action = 'execute';

            if (false !== stripos(substr($SQLRequest, 0, 10), 'SELECT')) {
                $action = 'executeS';
            }

            if (false !== stripos(substr($SQLRequest, 0, 10), 'SHOW TABLES LIKE')) {
                $action = 'ExecuteS';
            }

            return $this->db->{$action}($SQLRequest);
        } catch (\Exception $e) {
            var_dump($e);
        }
    }

    public function getLastId()
    {
        return $this->db->Insert_ID();
    }

    // @todo : A optimiser dans QueryRepository
    public function getValue($id)
    {
        return $this->db->getValue($id);
    }

    public function escape($string, $htmlOK = false)
    {
        return $this->db->escape($string, $htmlOK);
    }
}
