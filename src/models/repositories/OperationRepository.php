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
use PayplugUnifiedCore\Exceptions\PaymentNotFoundException;

class OperationRepository extends EntityRepository implements IPaymentRepository
{
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
