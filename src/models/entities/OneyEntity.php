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

class OneyEntity
{
    /** @var array */
    private $operations;

    /**
     * @param mixed $oneyXtimes
     *
     * @return array
     */
    public function getOperations($oneyXtimes = false)
    {
        // exclude oney Xtimes  in the checkout only for Belgium clients
        if ($oneyXtimes) {
            foreach ($oneyXtimes as $oney) {
                $result = array_search($oney, $this->operations, true);
                if ($result !== false) {
                    unset($this->operations[$result]);
                }
            }
        }

        return $this->operations;
    }

    /**
     * @param array $operations
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setOperations($operations)
    {
        if (!is_array($operations)) {
            throw (new BadParameterException('Invalid argument, $operations must be an array'));
        }

        $this->operations = $operations;

        return $this;
    }
}
