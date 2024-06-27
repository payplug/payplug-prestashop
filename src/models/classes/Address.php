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

namespace PayPlug\src\models\classes;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Address
{
    /** @var object */
    protected $address_adapter;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     *  @description  check if address exists, if not create it in DB
     *
     * @param array $user_address
     * @param int $customer_id
     * @param array $customer_addresses
     *
     * @return mixed|null
     */
    public function checkAndSaveAddress($user_address = [], $customer_id = 0, $customer_addresses = [])
    {
        if (!is_array($user_address) || empty($user_address)) {
            return 0;
        }

        if (!is_array($customer_addresses)) {
            return 0;
        }

        if (!is_int($customer_id)) {
            return 0;
        }
        $this->setParameters();
        $existing_address_id = 0;

        // Hash the user address
        $user_address_hash = hash('sha256', json_encode($user_address));

        if (!empty($customer_addresses)) {
            foreach ($customer_addresses as $address) {
                $customer_address_hash = hash(
                    'sha256',
                    json_encode(
                        [
                            'firstname' => $address['firstname'],
                            'lastname' => $address['lastname'],
                            'address1' => $address['address1'],
                            'postcode' => $address['postcode'],
                            'city' => $address['city'],
                            'id_country' => (int) $address['id_country'],
                        ]
                    )
                );

                // If the address exists, set the existing address ID
                if ($customer_address_hash === $user_address_hash) {
                    $existing_address_id = $address['id_address'];

                    break;
                }
            }
        }

        // Save the address if it doesn't exist
        if (!$existing_address_id) {
            $address = $this->address_adapter->get();
            $address->firstname = $user_address['firstname'];
            $address->lastname = $user_address['lastname'];
            $address->id_country = $user_address['id_country'];
            $address->address1 = $user_address['address1'];
            $address->postcode = $user_address['postcode'];
            $address->city = $user_address['city'];
            $address->phone_mobile = isset($user_address['mobile_phone_number']) ? $user_address['mobile_phone_number'] : '';
            $address->alias = 'Apple Pay Address';
            $address->id_customer = $customer_id;
            $this->address_adapter->saveAddress($address);
            $existing_address_id = $address->id;
        }

        return $existing_address_id;
    }

    /**
     * @description Set parameters for usage
     */
    protected function setParameters()
    {
        if (!$this->address_adapter) {
            $this->address_adapter = $this->dependencies->getPlugin()->getAddress();
        }
    }
}
