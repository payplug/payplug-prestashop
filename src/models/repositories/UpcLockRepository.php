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

class UpcLockRepository extends EntityRepository
{
    public function __construct($dependencies = null)
    {
        parent::__construct($dependencies);
        $this->entity_name = 'UpcLockEntity';
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
            ->fields('`id_payplug_upc_lock` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`lock_key` VARCHAR(255) NOT NULL')
            ->fields('`expires_at` INT(11) UNSIGNED NOT NULL')
            ->fields('`date_add` DATETIME NULL')
            ->fields('`date_upd` DATETIME NULL')
            ->condition('CONSTRAINT payplug_upc_lock_unique UNIQUE (lock_key)')
            ->engine($engine);

        return $this->build();
    }

    /**
     * @description Atomically steal an expired lock row: UPDATE ... WHERE lock_key = :key AND
     *              expires_at < :now in a single statement, re-checking the expiry condition at
     *              SQL level instead of a prior SELECT. Returns true only when this specific
     *              UPDATE affected exactly one row, i.e. only for the caller that actually won
     *              the steal race - two concurrent callers targeting the same expired (or
     *              already-gone) row can no longer both succeed, unlike a read-then-blind-UPDATE-
     *              by-id approach.
     *
     * @param string $lock_key
     * @param int $new_expires_at
     *
     * @return bool
     */
    public function stealExpired($lock_key = '', $new_expires_at = 0)
    {
        if (!is_string($lock_key) || !$lock_key) {
            return false;
        }
        if (!is_int($new_expires_at) || !$new_expires_at) {
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
            ->update()
            ->table($this->getTableName($definition['table']))
            ->set('expires_at = ' . (int) $new_expires_at)
            ->where('lock_key = "' . $this->escape($lock_key) . '"')
            ->where('expires_at < ' . time());

        $this->build();

        return 1 === $this->dependencies->getPlugin()->getQueryAdapter()->getAffectedRows();
    }
}
