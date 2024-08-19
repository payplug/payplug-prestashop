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

namespace PayPlug\src\models\repositories;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CardRepository extends EntityRepository
{
    public function __construct($dependencies = null)
    {
        parent::__construct($dependencies);
        $this->table_name = $this->dependencies->name . '_card';
        $this->entity_name = 'CardEntity';
    }

    /**
     * @description Check if a card is already register in the database
     *
     * @param string $payment_id
     * @param int $company_id
     * @param false $is_sandbox
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
        $entity = $this->getEntityObject($this->entity_name);
        if (!$entity) {
            return false;
        }
        $definition = $entity->getDefinition();

        $result = $this
            ->select()
            ->fields('id_card')
            ->from($this->getTableName($definition['table']))
            ->where('id_card = "' . $this->escape($payment_id) . '"')
            ->where('id_company = ' . (int) $company_id)
            ->where('is_sandbox = ' . ((bool) $is_sandbox ? 1 : 0))
            ->build('unique_value');

        return (bool) $result;
    }

    /**
     * @description Get all registered cards for a given customer
     *
     * @param int $id_customer
     * @param int $id_company
     * @param bool $is_sandbox
     *
     * @return array
     */
    public function getAllByCustomer($id_customer = 0, $id_company = 0, $is_sandbox = false)
    {
        if (!is_int($id_customer) || !$id_customer) {
            return [];
        }
        if (!is_int($id_company) || !$id_company) {
            return [];
        }
        if (!is_bool($is_sandbox)) {
            return [];
        }
        if (!is_string($this->entity_name) || !$this->entity_name) {
            return [];
        }

        $entity = $this->getEntityObject($this->entity_name);
        if (!$entity) {
            return [];
        }
        $definition = $entity->getDefinition();

        $this
            ->select()
            ->fields('*')
            ->from($this->getTableName($definition['table']))
            ->where('`id_customer` = ' . (int) $id_customer);
        if (null !== $id_company) {
            $this->where('`id_company` = ' . (int) $id_company);
        }

        if (null !== $is_sandbox) {
            $this->where('`is_sandbox` = ' . ((bool) $is_sandbox ? 1 : 0));
        }

        $result = $this->build();

        return $result ?: [];
    }

    /**
     * @description Create the table in the database
     *
     * @param string $engine
     *
     * @return bool
     */
    public function initialize($engine = '')
    {
        if (!is_string($engine) || !$engine) {
            return false;
        }
        if (!is_string($this->entity_name) || !$this->entity_name) {
            return false;
        }

        $entity = $this->getEntityObject($this->entity_name);
        if (!$entity) {
            return false;
        }
        $definition = $entity->getDefinition();

        $this
            ->create()
            ->table($this->getTableName($definition['table']))
            ->fields('`id_payplug_card` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`id_customer` int(11) UNSIGNED NOT NULL')
            ->fields('`id_company` int(11) UNSIGNED NOT NULL')
            ->fields('`is_sandbox` int(1) UNSIGNED NOT NULL')
            ->fields('`id_card` varchar(255) NOT NULL')
            ->fields('`last4` varchar(4) NOT NULL')
            ->fields('`exp_month` varchar(4) NOT NULL')
            ->fields('`exp_year` varchar(4) NOT NULL')
            ->fields('`brand` varchar(255) DEFAULT NULL')
            ->fields('`country` varchar(3) NOT NULL')
            ->fields('`metadata` varchar(255) DEFAULT NULL')
            ->engine($engine);

        return $this->build();
    }

    //TODO: to be deleted

    /**
     * @description Insert a new card in the database
     *
     * @param null $card
     * @param int $customer_id
     * @param int $company_id
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
        if (!is_string($this->entity_name) || !$this->entity_name) {
            return false;
        }

        $entity = $this->getEntityObject($this->entity_name);
        if (!$entity) {
            return false;
        }
        $definition = $entity->getDefinition();

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
            ->into($this->getTableName($definition['table']))
            ->fields('id_customer')->values((int) $customer_id)
            ->fields('id_company')->values((int) $company_id)
            ->fields('is_sandbox')->values((bool) $is_sandbox ? 1 : 0)
            ->fields('id_card')->values($this->escape($card->id))
            ->fields('last4')->values($this->escape($card->last4))
            ->fields('exp_month')->values($this->escape($card->exp_month))
            ->fields('exp_year')->values($this->escape($card->exp_year))
            ->fields('brand')->values($this->escape($card->brand))
            ->fields('country')->values($this->escape($card->country))
            ->fields('metadata')->values($this->escape(json_encode($card->metadata)))
            ->build();

        return (bool) $result;
    }
}
