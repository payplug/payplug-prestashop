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

use PayplugUnifiedCore\Contracts\IPaymentRepository;
use PayplugUnifiedCore\DataValues\OperationData;
use PayplugUnifiedCore\DataValues\PaymentOutcome;
use PayplugUnifiedCore\Exceptions\PaymentNotFoundException;

class OperationRepository extends EntityRepository implements IPaymentRepository
{
    private const PLACEHOLDER_ORDER_PREFIX = 'cart:';

    /**
     * Far above the 600 s token/pending-operation TTL that bounds a 3DS challenge, so a late
     * final notification still finds its placeholder.
     */
    private const PLACEHOLDER_MAX_AGE = 86400;

    public function __construct($dependencies = null)
    {
        parent::__construct($dependencies);
        $this->entity_name = 'OperationEntity';
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
            ->fields('`id_payplug_upc_operation` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`operation_id` VARCHAR(255) NOT NULL')
            ->fields('`order_id` VARCHAR(255) NOT NULL')
            ->fields('`exec_code` VARCHAR(20) NOT NULL')
            ->fields('`outcome` VARCHAR(30) NOT NULL')
            ->fields('`amount` INT(11) UNSIGNED NOT NULL')
            ->fields('`treated` TINYINT(1) NOT NULL DEFAULT 0')
            ->fields('`payment_id` VARCHAR(255) NULL')
            ->fields('`date_add` DATETIME NULL')
            ->fields('`date_upd` DATETIME NULL')
            ->condition('CONSTRAINT payplug_upc_operation_unique UNIQUE (operation_id), KEY payplug_upc_operation_order_id (order_id)')
            ->engine($engine);

        return $this->build();
    }

    public function getByOrderId(string $orderId): OperationData
    {
        $row = $this->getBy('order_id', $orderId);

        if (!$row) {
            throw new PaymentNotFoundException(sprintf('No UPC operation for order "%s".', $orderId));
        }

        return $this->toOperationData($row);
    }

    /**
     * An order can carry several operations (e.g. a FAILED one, then the PAID one that
     * reconciled the abandoned order) - getByOrderId() returns an arbitrary one of them, so
     * anything that needs the operation that actually captured the money (refunds) uses this.
     */
    public function getPaidByOrderId(string $orderId): ?OperationData
    {
        foreach ($this->getAllBy('order_id', $orderId) as $row) {
            if (PaymentOutcome::PAID === $row['outcome']) {
                return $this->toOperationData($row);
            }
        }

        return null;
    }

    public function getByOperationId(string $operationId): OperationData
    {
        $row = $this->getBy('operation_id', $operationId);

        if (!$row) {
            throw new PaymentNotFoundException(sprintf('No UPC operation "%s".', $operationId));
        }

        return $this->toOperationData($row);
    }

    public function save(OperationData $operationData): void
    {
        $existing = $this->getBy('operation_id', $operationData->operationId);

        $fields = [
            'operation_id' => $operationData->operationId,
            'order_id' => $operationData->orderId,
            'exec_code' => $operationData->execCode,
            'outcome' => $operationData->outcome,
            'amount' => (int) $operationData->amount,
        ];

        if ($existing) {
            $this->updateEntity((int) $existing['id_payplug_upc_operation'], $fields);

            return;
        }

        $fields['treated'] = false;
        $this->createEntity($fields);
    }

    /**
     * The Unified API identifies a payment and its operations separately: the payment-creation
     * response carries the payment's own "id" (the key POST /payments/{id}/refund expects) and
     * "operationIds[0]" (the key getOperation() and the webhook use, stored as operation_id).
     * OperationData has no room for the former, so it is bound here, right after the payment is
     * created - before any order exists for a 3DS payment. When the operation isn't persisted yet,
     * a pending placeholder row is created; save() later completes it (order_id, outcome, amount)
     * and, since it never writes payment_id, keeps the binding.
     *
     * The placeholder (order_id 'cart:<id_cart>', exec_code '0001', outcome three_ds_pending,
     * amount 0) is never completed when no order is ever created for the payment: a frictionless
     * payment refused synchronously (createFromOutcome() persists nothing for a FAILED outcome
     * without an order), or a 3DS challenge abandoned with no final notification. Such rows stay
     * "pending" although the payment failed or was dropped. Harmless for the module - only rows
     * with outcome PAID (getPaidByOrderId()) and the treated flag are ever read - but support
     * should not read them as payments still in progress. They are purged once older than
     * PLACEHOLDER_MAX_AGE, each time a new placeholder is created (no cron needed).
     */
    public function bindPaymentId(string $operationId, string $paymentId, int $idCart): bool
    {
        if ('' === $operationId || '' === $paymentId) {
            return false;
        }

        $existing = $this->getBy('operation_id', $operationId);

        if ($existing) {
            return (bool) $this->updateEntity((int) $existing['id_payplug_upc_operation'], ['payment_id' => $paymentId]);
        }

        $now = date('Y-m-d H:i:s');

        try {
            $created = (bool) $this->createEntity([
                'operation_id' => $operationId,
                'order_id' => self::PLACEHOLDER_ORDER_PREFIX . $idCart,
                'exec_code' => '0001',
                'outcome' => PaymentOutcome::THREE_DS_PENDING,
                'amount' => 0,
                'payment_id' => $paymentId,
                'treated' => false,
                'date_add' => $now,
                'date_upd' => $now,
            ]);
        } catch (\Throwable $exception) {
            $created = false;
        }

        if (!$created) {
            // The notification of a frictionless payment may have save()d the row between getBy()
            // and the insert, which then hits UNIQUE (operation_id): bind onto that row instead.
            $existing = $this->getBy('operation_id', $operationId);

            return $existing
                && (bool) $this->updateEntity((int) $existing['id_payplug_upc_operation'], ['payment_id' => $paymentId]);
        }

        $this->purgeStalePlaceholders();

        return true;
    }

    public function getPaymentIdByOperationId(string $operationId): ?string
    {
        $row = $this->getBy('operation_id', $operationId);

        return $row && isset($row['payment_id']) && is_string($row['payment_id']) && '' !== $row['payment_id']
            ? $row['payment_id']
            : null;
    }

    public function markTreated(string $operationId): void
    {
        $existing = $this->getBy('operation_id', $operationId);

        if (!$existing) {
            return;
        }

        $this->updateEntity((int) $existing['id_payplug_upc_operation'], ['treated' => true]);
    }

    public function isTreated(string $operationId): bool
    {
        $row = $this->getBy('operation_id', $operationId);

        return (bool) ($row && $row['treated']);
    }

    /**
     * @description Delete the placeholders bindPaymentId() created for payments that never got
     *              an order; a failure only delays the purge to the next placeholder
     */
    private function purgeStalePlaceholders(): void
    {
        $entity = $this->getEntityObject($this->entity_name);
        if (!$entity) {
            return;
        }

        $definition = $entity->getDefinition();

        try {
            $this
                ->delete()
                ->from($this->getTableName($definition['table']))
                ->where('`order_id` LIKE "' . self::PLACEHOLDER_ORDER_PREFIX . '%"')
                ->where('`outcome` = "' . PaymentOutcome::THREE_DS_PENDING . '"')
                ->where('`date_add` < "' . date('Y-m-d H:i:s', time() - self::PLACEHOLDER_MAX_AGE) . '"');

            $this->build();
        } catch (\Throwable $exception) {
        }
    }

    private function toOperationData(array $row): OperationData
    {
        return new OperationData(
            $row['operation_id'],
            $row['exec_code'],
            $row['outcome'],
            (int) $row['amount'],
            $row['order_id']
        );
    }
}
