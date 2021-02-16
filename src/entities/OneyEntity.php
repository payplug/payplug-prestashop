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

class OneyEntity
{
    /**
     * @var array $methods
     */
    private $methods;

    /**
     * @return array
     */
    public function getMethods(){
        return $this->methods;
    }

    /**
     * @param $methods
     * @return $this
     * @throws BadParameterException
     */
    public function setMethods($methods){
        if(!is_array($methods)) {
            throw (new BadParameterException('Invalid fields validate, param $methods must be an array'));
        } else {
            $this->methods = $methods;
            return $this;
        }
    }
}
