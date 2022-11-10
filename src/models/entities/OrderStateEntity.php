<?php
/**
 * 2013 - 2022 PayPlug SAS
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
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\models\entities;

use PayPlug\src\exceptions\BadParameterException;

class OrderStateEntity
{
    /** @var array */
    private $list;

    /**
     * @return object
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param $apiPayment
     * @param mixed $list
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setList($list)
    {
        if (!is_array($list)) {
            throw (new BadParameterException('Invalid argument, $list must be an array'));
        }

        $this->list = $list;

        return $this;
    }
}
