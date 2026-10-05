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

use PayPlug\src\interfaces\CustomerInterface;

class CustomerAdapter implements CustomerInterface
{
    public function get($idCustomer)
    {
        return new \Customer($idCustomer);
    }

    public function isLogged($customer)
    {
        return $customer->isLogged();
    }

    /**
     * @description  get customer's list of address
     *
     * @param $customer_id
     * @param int $id_lang
     *
     * @return mixed
     */
    public function getAddresses($customer_id = 0, $id_lang = 0)
    {
        if (!is_int($customer_id) || !$customer_id) {
            return [];
        }
        if (!is_int($id_lang) || !$id_lang) {
            return [];
        }
        $customer = new \Customer($customer_id);

        return $customer->getAddresses($id_lang);
    }

    /**
     * @description adapter to add customer object
     * in DB
     *
     * @param $customer
     *
     * @return bool
     */
    public function add($customer)
    {
        return $customer->add();
    }

    /**
     * @description Check if a customer exists by email and optionally return the customer ID
     *
     * @param string $email Customer email address
     * @param mixed $return_id
     *
     * @return bool|int Returns customer ID if $returnId is true and customer exists,
     *                  otherwise returns boolean indicating if customer exists
     */
    public function customerExists($email = '', $return_id = false)
    {
        if (!is_string($email) || empty($email)) {
            return 0;
        }

        return \Customer::customerExists($email, $return_id);
    }

    /**
     * @description Get the most recent active guest customer id for a given email
     * (registered accounts are ignored)
     *
     * @param string $email
     *
     * @return int
     */
    public function getGuestIdByEmail($email = '')
    {
        if (!is_string($email) || empty($email)) {
            return 0;
        }

        $customers = \Customer::getCustomersByEmail($email);
        if (!is_array($customers) || empty($customers)) {
            return 0;
        }

        $guest_id = 0;
        foreach ($customers as $customer) {
            if (!is_array($customer)
                || !isset($customer['id_customer'], $customer['is_guest'], $customer['deleted'], $customer['active'])) {
                continue;
            }
            if (!(bool) $customer['is_guest'] || (bool) $customer['deleted'] || !(bool) $customer['active']) {
                continue;
            }
            if ((int) $customer['id_customer'] > $guest_id) {
                $guest_id = (int) $customer['id_customer'];
            }
        }

        return $guest_id;
    }
}
