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

class PaymentRepository extends EntityRepository
{
    public function __construct($dependencies = null)
    {
        parent::__construct($dependencies);
        $this->entity_name = 'PaymentEntity';
    }

    /**
     * @description Get all payments for a given method name.
     *
     * @param string $method_name
     * @param bool $asc
     *
     * @return array
     */
    public function getAllByMethod($method_name = '', $asc = false)
    {
        if (!is_string($method_name) || !$method_name) {
            return [];
        }
        if (!is_bool($asc)) {
            return [];
        }
        if (!is_string($this->entity_name) || !$this->entity_name) {
            return false;
        }
        $entity = $this->getEntityObject($this->entity_name);
        if (!$entity) {
            return false;
        }
        $definition = $entity->getDefinition();

        $query = $this
            ->select()
            ->fields('*')
            ->from($this->getTableName($definition['table']))
            ->where('`method` = "' . $this->escape($method_name) . '"');

        if ($asc) {
            $query->orderBy('`id_payplug_payment` DESC');
        }

        return $query->build() ?: [];
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
            ->fields('`id_payplug_payment` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`resource_id` VARCHAR(255) NULL')
            ->fields('`method` VARCHAR(255) NULL')
            ->fields('`id_cart` INT(11) UNSIGNED NOT NULL')
            ->fields('`cart_hash` VARCHAR(64) NULL')
            ->fields('`schedules` TEXT NULL')
            ->fields('`date_upd` DATETIME NULL')
            ->condition('CONSTRAINT lock_cart_unique UNIQUE (id_cart)')
            ->engine($engine);

        return $this->build();
    }
}
