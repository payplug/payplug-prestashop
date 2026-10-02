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
    protected $dependencies;

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
     * @param bool $complete_phone_mobile complete a matching address which has no mobile phone (guest flow only)
     *
     * @return mixed|null
     */
    public function checkAndSaveAddress($user_address = [], $customer_id = 0, $customer_addresses = [], $complete_phone_mobile = false)
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

        if (!is_bool($complete_phone_mobile)) {
            return 0;
        }
        $this->setParameters();
        $existing_address_id = 0;
        $existing_phone_mobile = '';

        $user_address_hash = hash('sha256', json_encode([
            'firstname' => $user_address['firstname'],
            'lastname' => $user_address['lastname'],
            'address1' => $user_address['address1'],
            'address2' => isset($user_address['address2']) ? $user_address['address2'] : '',
            'postcode' => $user_address['postcode'],
            'city' => $user_address['city'],
            'id_country' => (int) $user_address['id_country'],
        ]));

        if (!empty($customer_addresses)) {
            foreach ($customer_addresses as $address) {
                $customer_address_hash = hash(
                    'sha256',
                    json_encode(
                        [
                            'firstname' => $address['firstname'],
                            'lastname' => $address['lastname'],
                            'address1' => $address['address1'],
                            'address2' => isset($address['address2']) ? $address['address2'] : '',
                            'postcode' => $address['postcode'],
                            'city' => $address['city'],
                            'id_country' => (int) $address['id_country'],
                        ]
                    )
                );

                // If the address exists, set the existing address ID
                if ($customer_address_hash === $user_address_hash) {
                    $existing_address_id = $address['id_address'];
                    $existing_phone_mobile = isset($address['phone_mobile']) ? trim((string) $address['phone_mobile']) : '';

                    break;
                }
            }
        }

        // Complete the existing address with the given mobile phone if it has none
        // (an existing phone is never overwritten, guest flow only)
        if ($existing_address_id) {
            $user_phone_mobile = isset($user_address['phone_mobile']) ? trim((string) $user_address['phone_mobile']) : '';
            if ($complete_phone_mobile && '' === $existing_phone_mobile && '' !== $user_phone_mobile) {
                $this->updateAddressPhoneMobile((int) $existing_address_id, $user_phone_mobile);
            }

            return $existing_address_id;
        }

        // Save the address as it doesn't exist
        $address = $this->address_adapter->get();
        $address->firstname = $user_address['firstname'];
        $address->lastname = $user_address['lastname'];
        $address->id_country = $user_address['id_country'];
        $address->address1 = $user_address['address1'];
        $address->address2 = isset($user_address['address2']) ? $user_address['address2'] : '';
        $address->postcode = $user_address['postcode'];
        $address->city = $user_address['city'];
        $address->phone_mobile = isset($user_address['phone_mobile']) ? $user_address['phone_mobile'] : '';
        // Hash-based alias gives each distinct Apple Pay address a unique alias,
        $address->alias = 'Apple Pay - ' . substr($user_address_hash, 0, 8);
        $address->id_customer = $customer_id;
        $this->address_adapter->saveAddress($address);

        return $address->id;
    }

    /**
     * @description Set the mobile phone of an existing address
     *
     * @param int $id_address
     * @param string $phone_mobile
     *
     * @return bool
     */
    protected function updateAddressPhoneMobile($id_address = 0, $phone_mobile = '')
    {
        if (!is_int($id_address) || !$id_address) {
            return false;
        }
        if (!is_string($phone_mobile) || '' === $phone_mobile) {
            return false;
        }

        $address = $this->address_adapter->get($id_address);
        // Do not save an address that couldn't be loaded, it would create a new one
        if (!is_object($address) || (int) $address->id !== $id_address) {
            return false;
        }

        // Keep an address already used by an order unchanged, otherwise past orders would show the new phone
        if ($this->address_adapter->isUsed($address)) {
            return false;
        }

        $address->phone_mobile = $phone_mobile;

        return (bool) $this->address_adapter->saveAddress($address);
    }

    /**
     * @description Set parameters for usage
     */
    protected function setParameters()
    {
        $this->address_adapter = $this->dependencies->getPlugin()->getAddress();
    }
}
