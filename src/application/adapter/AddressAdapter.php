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

use PayPlug\src\interfaces\AddressInterface;

class AddressAdapter implements AddressInterface
{
    private $address;

    public function __construct()
    {
        $this->address = new \Address();
    }

    public function get($idAddress = false)
    {
        if (!is_int($idAddress)) {
            $idAddress = false;
        }

        return new \Address($idAddress);
    }

    public function getZoneById($idAddress = false)
    {
        if (!is_int($idAddress)) {
            $idAddress = false;
        }

        $address = $this->address;

        return $address::getZoneById($idAddress);
    }

    /**
     * @description adapter to save address object
     * in ps_address
     *
     * @param $address
     *
     * @return mixed
     */
    public function saveAddress($address)
    {
        return $address->save();
    }
}
