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

class LockRepository extends EntityRepository
{
    public function __construct($dependencies = null)
    {
        parent::__construct($dependencies);
        $this->entity_name = 'LockEntity';
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
            ->fields('`id_payplug_lock` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`id_cart` INT(11) UNSIGNED NOT NULL')
            ->fields('`id_order` VARCHAR(100)')
            ->fields('`date_add` DATETIME NOT NULL DEFAULT \'1000-01-01 00:00:00\'')
            ->fields('`date_upd` DATETIME NOT NULL DEFAULT \'1000-01-01 00:00:00\'')
            ->condition('CONSTRAINT lock_cart_unique UNIQUE (id_cart)')
            ->engine($engine);

        return $this->build();
    }
}
