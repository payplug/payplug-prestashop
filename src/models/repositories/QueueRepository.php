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

class QueueRepository extends EntityRepository
{
    public function __construct($dependencies = null)
    {
        parent::__construct($dependencies);
        $this->entity_name = 'QueueEntity';
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
            ->fields('`' . $definition['primary'] . '` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`id_cart` INT(11) UNSIGNED NOT NULL')
            ->fields('`resource_id` VARCHAR(255) NULL')
            ->fields('`type` VARCHAR(64) NOT NULL')
            ->fields('`date_add` DATETIME NULL')
            ->fields('`date_upd` DATETIME NULL')
            ->fields('`treated` TINYINT(1) NOT NULL DEFAULT 0')
            ->condition('CONSTRAINT ' . $definition['table'] . '_unique UNIQUE (' . $definition['primary'] . ')')
            ->engine($engine);

        return $this->build();
    }

    /**
     * @description Get first non treated entry of a queue from id cart.
     *
     * @param int $cart_id
     *
     * @return array
     */
    public function getFirstNotTreatedEntry($cart_id = 0)
    {
        if (!is_int($cart_id) || !$cart_id) {
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
        if (!is_array($definition) || !isset($definition['table'])) {
            return [];
        }
        $this
            ->select()
            ->fields('*')
            ->from($this->getTableName($definition['table']))
            ->where('`id_cart` = ' . (int) $cart_id)
            ->where('`treated` =  0')
            ->orderBy($definition['primary'] . ' ASC')
            ->limit(1);
        $result = $this->build('unique_row') ?: [];
        if (!$result) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('QueueRepository::getFirstNotTreatedEntry() - No non-treated entry found for Cart ID: ' . $cart_id, 'error');
        }

        return $result;
    }
}
