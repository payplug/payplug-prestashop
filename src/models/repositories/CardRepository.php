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

namespace PayPlug\src\models\repositories;

class CardRepository extends QueryRepository
{
    /**
     * @description Register a card from the api
     *
     * @param null  $card
     * @param int   $customer_id
     * @param int   $company_id
     * @param false $is_sandbox
     *
     * @return bool
     */
    public function set($card = null, $customer_id = 0, $company_id = 0, $is_sandbox = false)
    {
        if (!is_object($card) || !$card) {
            return false;
        }

        if (!is_int($customer_id) || !$customer_id) {
            return false;
        }

        if (!is_int($company_id) || !$company_id) {
            return false;
        }

        if (!is_bool($is_sandbox)) {
            return false;
        }

        $fields = [
            'id',
            'last4',
            'exp_month',
            'exp_year',
            'brand',
            'country',
            'metadata',
        ];
        foreach ($fields as $field) {
            if (!isset($card->{$field})) {
                return false;
            }
        }

        $result = $this
            ->insert()
            ->into($this->prefix . $this->module_name . '_card')
            ->fields('id_customer')->values((int) $customer_id)
            ->fields('id_company')->values((int) $company_id)
            ->fields('is_sandbox')->values((bool) $is_sandbox ? 1 : 0)
            ->fields('id_card')->values($this->escape($card->id))
            ->fields('last4')->values($this->escape($card->last4))
            ->fields('exp_month')->values($this->escape($card->exp_month))
            ->fields('exp_year')->values($this->escape($card->exp_year))
            ->fields('brand')->values($this->escape($card->brand))
            ->fields('country')->values($this->escape($card->country))
            ->fields('metadata')->values($this->escape(serialize($card->metadata)))
            ->build();

        return (bool) $result;
    }

    /**
     * @description Delete a card from a given id
     *
     * @param int $id_payplug_card
     *
     * @return bool
     */
    public function remove($id_payplug_card = 0)
    {
        if (!is_int($id_payplug_card) || !$id_payplug_card) {
            return false;
        }

        $result = $this
            ->delete()
            ->from($this->prefix . $this->module_name . '_card')
            ->where('`id_payplug_card` = ' . (int) $id_payplug_card)
            ->build();

        return (bool) $result;
    }

    /**
     * @description Get a card from a given id
     *
     * @param int $id_payplug_card
     *
     * @return array
     */
    public function get($id_payplug_card = 0)
    {
        if (!is_int($id_payplug_card) || !$id_payplug_card) {
            return [];
        }

        $result = $this
            ->select()
            ->fields('*')
            ->from($this->prefix . $this->module_name . '_card')
            ->where('`id_payplug_card` = ' . (int) $id_payplug_card)
            ->build('unique_row');

        return $result ? $result : [];
    }

    /**
     * @description Check if a card is already register in the database
     *
     * @param string $payment_id
     * @param int    $company_id
     * @param false  $is_sandbox
     *
     * @return bool
     */
    public function exists($payment_id = '', $company_id = 0, $is_sandbox = false)
    {
        if (!is_string($payment_id) || !$payment_id) {
            return false;
        }

        if (!is_int($company_id) || !$company_id) {
            return false;
        }

        if (!is_bool($is_sandbox)) {
            return false;
        }

        $result = $this
            ->select()
            ->fields('id_card')
            ->from($this->prefix . $this->module_name . '_card')
            ->where('id_card = "' . $this->escape($payment_id) . '"')
            ->where('id_company = ' . (int) $company_id)
            ->where('is_sandbox = ' . ((bool) $is_sandbox ? 1 : 0))
            ->build('unique_value');

        return $result ? true : false;
    }

    /**
     * @description Get all registered cards
     *
     * @return array
     */
    public function getAll()
    {
        $result = $this
            ->select()
            ->fields('*')
            ->from($this->prefix . $this->module_name . '_card')
            ->build();

        return $result ? $result : [];
    }

    /**
     * @description Get all registered cards for a given customer
     *
     * @param int   $id_customer
     * @param int   $id_company
     * @param false $is_sandbox
     *
     * @return array
     */
    public function getAllByCustomer($id_customer = 0, $id_company = null, $is_sandbox = null)
    {
        if (!is_int($id_customer) || !$id_customer) {
            return [];
        }
        if (!is_int($id_company) && $id_company !== null || $id_company === 0) {
            return [];
        }
        if (!is_bool($is_sandbox) && $is_sandbox !== null) {
            return [];
        }

        $this
            ->select()
            ->fields('*')
            ->from($this->prefix . $this->module_name . '_card')
            ->where('`id_customer` = ' . (int) $id_customer);

        if ($id_company !== null) {
            $this->where('`id_company` = ' . (int) $id_company);
        }

        if ($is_sandbox !== null) {
            $this->where('`is_sandbox` = ' . ((bool) $is_sandbox ? 1 : 0));
        }

        $result = $this->build();

        return $result ? $result : [];
    }
}
