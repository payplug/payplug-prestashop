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

class UpcRefundRepository extends EntityRepository
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_FAILED = 'failed';

    /**
     * @description Set up the repository for the UpcRefundEntity
     *
     * @param mixed|null $dependencies
     */
    public function __construct($dependencies = null)
    {
        parent::__construct($dependencies);
        $this->entity_name = 'UpcRefundEntity';
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
            ->fields('`id_payplug_upc_refund` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`refund_operation_id` VARCHAR(255) NOT NULL')
            ->fields('`payment_operation_id` VARCHAR(255) NOT NULL')
            ->fields('`order_id` VARCHAR(255) NOT NULL')
            ->fields('`amount` INT(11) UNSIGNED NOT NULL')
            ->fields('`currency` VARCHAR(3) NOT NULL')
            ->fields('`status` VARCHAR(20) NOT NULL')
            ->fields('`date_add` DATETIME NULL')
            ->fields('`date_upd` DATETIME NULL')
            ->condition('CONSTRAINT payplug_upc_refund_unique UNIQUE (refund_operation_id), KEY payplug_upc_refund_order_id (order_id)')
            ->engine($engine);

        return $this->build();
    }

    /**
     * @description Record a refund in the pending status, before the Unified API is even
     *              called (under a placeholder id, see bindRefundOperationId()), until its
     *              asynchronous notification confirms or fails it
     *
     * @param string $refund_operation_id
     * @param string $payment_operation_id
     * @param string $order_id
     * @param int $amount in cents
     * @param string $currency
     *
     * @return bool
     */
    public function addRefund($refund_operation_id, $payment_operation_id, $order_id, $amount, $currency)
    {
        if (!is_int($amount) || $amount <= 0) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        return (bool) $this->createEntity([
            'refund_operation_id' => (string) $refund_operation_id,
            'payment_operation_id' => (string) $payment_operation_id,
            'order_id' => (string) $order_id,
            'amount' => $amount,
            'currency' => (string) $currency,
            'status' => self::STATUS_PENDING,
            'date_add' => $now,
            'date_upd' => $now,
        ]);
    }

    /**
     * @description Replace the placeholder id of a refund recorded before the API call by the
     *              refund's own operation id, the key its notification is matched on
     *
     * @param string $placeholder_id
     * @param string $refund_operation_id
     *
     * @return bool
     */
    public function bindRefundOperationId($placeholder_id, $refund_operation_id)
    {
        if (!is_string($placeholder_id) || !$placeholder_id) {
            return false;
        }
        if (!is_string($refund_operation_id) || !$refund_operation_id) {
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
            ->set('refund_operation_id = "' . $this->escape($refund_operation_id) . '"')
            ->set('date_upd = "' . date('Y-m-d H:i:s') . '"')
            ->where('refund_operation_id = "' . $this->escape($placeholder_id) . '"');

        $this->build();

        return 1 === $this->dependencies->getPlugin()->getQueryAdapter()->getAffectedRows();
    }

    /**
     * @description Get a recorded refund by the Unified API operation id of the refund itself
     *
     * @param string $refund_operation_id
     *
     * @return array|null
     */
    public function getByRefundOperationId($refund_operation_id)
    {
        if (!is_string($refund_operation_id) || !$refund_operation_id) {
            return null;
        }

        $row = $this->getBy('refund_operation_id', $refund_operation_id);

        return $row ?: null;
    }

    /**
     * @description Total already refunded for an order, in cents. Failed refunds never moved
     *              money, so they don't count against the remaining refundable amount.
     *
     * @param string $order_id
     *
     * @return int
     */
    public function getRefundedAmount($order_id)
    {
        if (!is_string($order_id) || !$order_id) {
            return 0;
        }

        $total = 0;
        foreach ($this->getAllBy('order_id', $order_id) as $row) {
            if (self::STATUS_FAILED === $row['status']) {
                continue;
            }
            $total += (int) $row['amount'];
        }

        return $total;
    }

    /**
     * @description Settle a pending refund, in a single conditional UPDATE so a redelivered
     *              notification can never settle the same refund twice
     *
     * @param string $refund_operation_id
     * @param string $status
     *
     * @return bool
     */
    public function updateStatusIfPending($refund_operation_id, $status)
    {
        if (!is_string($refund_operation_id) || !$refund_operation_id) {
            return false;
        }
        if (!in_array($status, [self::STATUS_CONFIRMED, self::STATUS_FAILED], true)) {
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
            ->set('status = "' . $this->escape($status) . '"')
            ->set('date_upd = "' . date('Y-m-d H:i:s') . '"')
            ->where('refund_operation_id = "' . $this->escape($refund_operation_id) . '"')
            ->where('status = "' . self::STATUS_PENDING . '"');

        $this->build();

        return 1 === $this->dependencies->getPlugin()->getQueryAdapter()->getAffectedRows();
    }
}
