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

class AliasRepository extends EntityRepository
{
    /**
     * @description Set up the repository for the AliasEntity
     *
     * @param mixed|null $dependencies
     */
    public function __construct($dependencies = null)
    {
        parent::__construct($dependencies);
        $this->entity_name = 'AliasEntity';
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
            ->fields('`id_payplug_alias` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`id_customer` INT(11) UNSIGNED NOT NULL')
            ->fields('`alias_id` VARCHAR(255) NOT NULL')
            ->fields('`currency` VARCHAR(3) NOT NULL')
            ->fields('`identifier` VARCHAR(255) NOT NULL')
            ->fields('`brand` VARCHAR(50) DEFAULT NULL')
            ->fields('`last4` VARCHAR(4) DEFAULT NULL')
            ->fields('`exp_month` VARCHAR(2) DEFAULT NULL')
            ->fields('`exp_year` VARCHAR(4) DEFAULT NULL')
            ->fields('`date_add` DATETIME NULL')
            ->condition('CONSTRAINT payplug_alias_unique UNIQUE (alias_id), KEY payplug_alias_id_customer (id_customer)')
            ->engine($engine);

        return $this->build();
    }

    /**
     * @description Find an alias usable by a customer for a currency and UHF identifier
     *
     * One query for all four constraints: an unknown alias, another customer's alias, or an alias
     * created for another currency/identifier all resolve to the same null result.
     *
     * @param string $alias_id
     * @param int $id_customer
     * @param string $currency lowercase ISO code
     * @param string $identifier
     *
     * @return array|null
     */
    public function findUsable($alias_id = '', $id_customer = 0, $currency = '', $identifier = '')
    {
        if (!is_string($alias_id) || !$alias_id) {
            return null;
        }
        if (!is_int($id_customer) || $id_customer <= 0) {
            return null;
        }
        if (!is_string($currency) || !$currency) {
            return null;
        }
        if (!is_string($identifier) || !$identifier) {
            return null;
        }
        if (!is_string($this->entity_name) || !$this->entity_name) {
            return null;
        }
        $entity = $this->getEntityObject($this->entity_name);
        if (!$entity) {
            return null;
        }
        $definition = $entity->getDefinition();

        $result = $this
            ->select()
            ->fields('*')
            ->from($this->getTableName($definition['table']))
            ->where('`alias_id` = "' . $this->escape($alias_id) . '"')
            ->where('`id_customer` = ' . (int) $id_customer)
            ->where('`currency` = "' . $this->escape($currency) . '"')
            ->where('`identifier` = "' . $this->escape($identifier) . '"')
            ->build('unique_row');

        return $result ?: null;
    }

    /**
     * @description Get every alias of a customer, whatever its currency or identifier
     *
     * @param int $id_customer
     *
     * @return array
     */
    public function getAllByCustomer($id_customer = 0)
    {
        if (!is_int($id_customer) || $id_customer <= 0) {
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

        $result = $this
            ->select()
            ->fields('*')
            ->from($this->getTableName($definition['table']))
            ->where('`id_customer` = ' . (int) $id_customer)
            ->build();

        return $result ?: [];
    }

    /**
     * @description Insert an alias unless one with the same alias_id already exists
     *
     * Idempotent: the same PAID outcome can be applied several times (return then notify). The
     * UNIQUE (alias_id) constraint covers the residual race between the read and the insert.
     *
     * @param int $id_customer
     * @param string $alias_id
     * @param string $currency lowercase ISO code
     * @param string $identifier
     * @param string $brand
     * @param string|null $last4
     * @param string|null $exp_month
     * @param string|null $exp_year
     *
     * @return bool true when the alias exists after the call
     */
    public function saveIfAbsent(
        $id_customer,
        $alias_id,
        $currency,
        $identifier,
        $brand,
        $last4 = null,
        $exp_month = null,
        $exp_year = null
    ) {
        if (!is_int($id_customer) || $id_customer <= 0) {
            return false;
        }
        if (!is_string($alias_id) || !$alias_id) {
            return false;
        }
        if (!is_string($currency) || !$currency) {
            return false;
        }
        if (!is_string($identifier) || !$identifier) {
            return false;
        }

        if ($this->getBy('alias_id', $alias_id)) {
            return true;
        }

        $fields = [
            'id_customer' => $id_customer,
            'alias_id' => $alias_id,
            'currency' => $currency,
            'identifier' => $identifier,
            'brand' => (string) $brand,
            'date_add' => date('Y-m-d H:i:s'),
        ];
        foreach (['last4' => $last4, 'exp_month' => $exp_month, 'exp_year' => $exp_year] as $key => $value) {
            if (is_string($value) && '' !== $value) {
                $fields[$key] = $value;
            }
        }

        return (bool) $this->createEntity($fields);
    }
}
