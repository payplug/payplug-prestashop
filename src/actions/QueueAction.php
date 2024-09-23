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

namespace PayPlug\src\actions;

if (!defined('_PS_VERSION_')) {
    exit;
}

class QueueAction
{
    private $dependencies;
    private $queue_time;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->queue_time = 5;
    }

    /**
     * @description Check if a queue exists for the given cart id and create an entry
     *
     * @param int $id_cart
     * @param string $resource_id
     * @param string $type
     *
     * @return array
     */
    public function hydrateAction($id_cart = 0, $resource_id = '', $type = 'payment')
    {
        if (!is_int($id_cart) || !$id_cart) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('QueueAction::hydrateAction() - Invalid argument given, $id_cart must be a non null integer.');

            return [
                'result' => false,
            ];
        }

        if (!is_string($resource_id) || !$resource_id) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('QueueAction::hydrateAction() - Invalid argument given, $resource_id must be a non empty string.');

            return [
                'result' => false,
            ];
        }

        if (!is_string($type) || !$type) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('QueueAction::hydrateAction() - Invalid argument given, $type must be a non empty string.');

            return [
                'result' => false,
            ];
        }

        // check if exists
        $entry = $this->dependencies
            ->getPlugin()
            ->getQueueRepository()
            ->getFirstNotTreatedEntry((int) $id_cart);

        $exists = !empty($entry);

        $current_date = date('Y-m-d H:i:s');
        $date_now = strtotime($current_date);
        // 5 minutes life time for a queue
        $future_date = $date_now - (60 * $this->queue_time);
        $expiration_date = date('Y-m-d H:i:s', $future_date);

        $fields = [
            'id_cart' => $id_cart,
            'resource_id' => $resource_id,
            'type' => $type,
            'date_add' => $current_date,
            'date_upd' => $current_date,
            'treated' => false,
        ];

        if ($exists
            && $entry['date_add'] > $expiration_date) {
            // queue expired
            $fields['treated'] = true;
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('QueueAction::hydrateAction() - Treating expired queue' . $entry['id_payplug_queue']);

            $update = (bool) $this->dependencies
                ->getPlugin()
                ->getQueueRepository()
                ->updateEntity($entry['id_payplug_queue'], $fields);

            if (!$update) {
                $this->dependencies
                    ->getPlugin()
                    ->getLogger()
                    ->addLog('QueueAction::hydrateAction() - Error during treatment of expired queue', 'error');
            }

            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('QueueAction::hydrateAction() - Expired queue treated');

            return $this->hydrateAction($id_cart, $resource_id, $type);
        }

        $create = (bool) $this->dependencies
            ->getPlugin()
            ->getQueueRepository()
            ->createEntity($fields);

        return [
            'exists' => $exists,
            'result' => $create,
        ];
    }

    /**
     * @description Update queue in database
     *
     * @param int $id_cart
     *
     * @return array
     */
    public function updateAction($id_cart = 0)
    {
        if (!is_int($id_cart) || !$id_cart) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('QueueAction::updateAction() - Invalid argument given, $id_cart must be a non null integer.');

            return [
                'result' => false,
            ];
        }

        $entry_to_update = $this->dependencies
            ->getPlugin()
            ->getQueueRepository()
            ->getFirstNotTreatedEntry((int) $id_cart);
        if (empty($entry_to_update)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('QueueAction::updateAction() - No queue found to update');

            return [
                'result' => false,
            ];
        }

        $fields = [
            'treated' => true,
            'date_upd' => date('Y-m-d H:i:s'),
        ];

        $update = (bool) $this->dependencies
            ->getPlugin()
            ->getQueueRepository()
            ->updateEntity((int) $entry_to_update['id_payplug_queue'], $fields);

        if (!$update) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('QueueAction::updateAction() - Can\'t update the current queue.');

            return [
                'result' => false,
            ];
        }

        $exists = $this->dependencies
            ->getPlugin()
            ->getQueueRepository()
            ->getFirstNotTreatedEntry((int) $id_cart);

        return [
            'exists' => $exists,
            'result' => $update,
        ];
    }
}
