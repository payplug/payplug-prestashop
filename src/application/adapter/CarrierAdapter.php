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

namespace PayPlug\src\application\adapter;

use Carrier;
use PayPlug\src\interfaces\CarrierInterface;

class CarrierAdapter implements CarrierInterface
{
    /** @var int Default delivery delay value in days for new carrier */
    public $default_delay = 0;

    /** @var string Default delivery type value for new carrier */
    public $default_delivery_type = 'storepickup';

    public static function factory()
    {
        return new self();
    }

    public function get($id_carrier = false)
    {
        if (!is_int($id_carrier)) {
            $id_carrier = false;
        }

        return new Carrier($id_carrier);
    }

    public function getDefaultDelay()
    {
        return $this->default_delay;
    }

    public function getDefaultDeliveryType()
    {
        return $this->default_delivery_type;
    }

    public function getCarriers($id_lang, $active = false, $delete = false, $id_zone = false, $ids_group = null, $modules_filters = 1)
    {
        return Carrier::getCarriers($id_lang, $active, $delete, $id_zone, $ids_group, $modules_filters);
    }
}
