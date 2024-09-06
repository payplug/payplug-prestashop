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

class ValidationAction
{
    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @description Check if order is created or force creation
     *
     * @param bool $last_try
     *
     * @return array
     */
    public function checkAction($last_try = false)
    {
        if (!is_bool($last_try)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Validation::checkAction - $last_try must be a boolean', 'error');

            return [
                'result' => false,
                'action' => 'redirect',
                'redirected_url' => $this->getOrderLinks()['error'],
            ];
        }

        $context = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get();
        // Check if an order exists this related cart
        $id_order = $this->dependencies
            ->getPlugin()
            ->getOrder()
            ->getIdByCartId((int) $context->cart->id);
        if ($id_order) {
            return [
                'result' => true,
                'action' => 'redirect',
                'redirected_url' => $this->getOrderLinks($id_order)['confirm'],
            ];
        }

        // If last try, create remove current lock
        if (!$last_try) {
            return [
                'result' => true,
                'action' => 'wait',
            ];
        }

        $fields = [
            'treated' => true,
            'date_upd' => date('Y-m-d H:i:s'),
        ];
        $update = (bool) $this->dependencies
            ->getPlugin()
            ->getQueueRepository()
            ->updateBy('id_cart', (int) $context->cart->id, $fields);
        if (!$update) {
            return [
                'result' => false,
                'action' => 'redirect',
                'redirected_url' => $this->getOrderLinks()['error'],
            ];
        }

        // Then create order before redirect user
        $order_create = $this->createOrder((int) $context->cart->id);

        // if result is false an error has been occured
        if (!$order_create['result']) {
            return [
                'result' => false,
                'action' => 'redirect',
                'redirected_url' => $this->getOrderLinks()['error'],
            ];
        }

        // Clear lock before rendering it
        $this->clearLock();

        return [
            'result' => true,
            'action' => 'redirect',
            'redirected_url' => $this->getOrderLinks((int) $order_create['id_order'])['confirm'],
        ];
    }

    /**
     * @description Clear related lock
     *
     * @return bool
     */
    public function clearLock()
    {
        $context = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get();

        if ($this->dependencies->configClass->isValidFeature('feature_queueing_system')) {
            return (bool) $this->dependencies
                ->getPlugin()
                ->getQueueAction()
                ->updateAction((int) $context->cart->id)['result'];
        }

        return $this->dependencies
            ->getPlugin()
            ->getLockRepository()
            ->deleteLock((int) $context->cart->id);
    }

    /**
     * @description Create the order for a given cart id
     *
     * @param int $cart_id
     *
     * @return array|bool[]|false[]
     */
    public function createOrder($cart_id = 0)
    {
        if (!is_int($cart_id) || !$cart_id) {
            return [
                'result' => false,
            ];
        }

        $context = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get();

        // Check if a resource exists for the context cart id...
        $stored_payment = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('id_cart', (int) $context->cart->id);
        if (empty($stored_payment)) {
            return [
                'result' => false,
            ];
        }

        // ...then if no lock is present to create order
        $locked_setted = $this->setLock($stored_payment['resource_id']);
        if (!$locked_setted) {
            return [
                'result' => true,
            ];
        }
        $order_create = $this->dependencies
            ->getPlugin()
            ->getOrderAction()
            ->createAction($stored_payment['resource_id']);
        if (!$order_create['result']) {
            return [
                'result' => false,
            ];
        }

        return [
            'result' => true,
            'id_order' => $order_create['id_order'],
        ];
    }

    /**
     * @description Get the order links
     *
     * @param int $id_order
     *
     * @return array
     */
    public function getOrderLinks($id_order = 0)
    {
        $context = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get();
        $confirmation_url_fields = [];
        if (is_int($id_order) && $id_order) {
            $order = $this->dependencies
                ->getPlugin()
                ->getOrder()
                ->get((int) $id_order);
            $confirmation_url_fields = [
                'id_cart' => $context->cart->id,
                'id_module' => $this->dependencies->getPlugin()->getModule()->getInstanceByName($this->dependencies->name)->id,
                'id_order' => $id_order,
                'key' => $order->secure_key,
            ];
        }

        return [
            'confirm' => $context->link->getPageLink('order-confirmation', true, $context->language->id, $confirmation_url_fields),
            'error' => $context->link->getPageLink('order', true, $context->language->id, [
                'step' => 3,
                'has_error' => 1,
                'modulename' => $this->dependencies->name,
            ]),
            'cancel' => $context->link->getPageLink('order', true, $context->language->id, [
                'step' => 3,
            ]),
        ];
    }

    /**
     * @description Set lock
     *
     * @param string $resource_id
     *
     * @return bool
     */
    public function setLock($resource_id = '')
    {
        if (!is_string($resource_id) || !$resource_id) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Validation::setLock - $resource_id must be an non empty string', 'error');

            return false;
        }

        $context = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get();

        if ($this->dependencies->configClass->isValidFeature('feature_queueing_system')) {
            $exists = $this->dependencies
                ->getPlugin()
                ->getQueueRepository()
                ->getFirstNotTreatedEntry((int) $context->cart->id);

            if ($exists) {
                return false;
            }

            return (bool) $this->dependencies
                ->getPlugin()
                ->getQueueAction()
                ->hydrateAction((int) $context->cart->id, $resource_id)['result'];
        }

        return (bool) $this->dependencies->payplugLock->createLockG2((int) $context->cart->id, 'validation');
    }

    /**
     * @description Process the validation from a process and a cart id
     *
     * @param int $ps
     * @param int $cart_id
     *
     * @return array
     */
    public function validateAction($ps = 0, $cart_id = 0)
    {
        // Check if given cart id valid and is the same than from context
        if (!is_int($cart_id) || !$cart_id) {
            return [
                'result' => false,
                'url' => $this->getOrderLinks()['error'],
                'message' => 'Invalid argument given, $cart_id must be a non null integer.',
            ];
        }
        $context = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get();
        if ($context->cart->id != $cart_id) {
            return [
                'result' => false,
                'url' => $this->getOrderLinks()['error'],
                'message' => 'Given cart id did not match with context cart id.',
            ];
        }

        // ...then check if given ps is valid
        switch (true) {
            case !is_int($ps):
            case !in_array($ps, [1, 2]):
                return [
                    'result' => false,
                    'url' => $this->getOrderLinks()['error'],
                    'message' => 'Invalid argument ps given',
                ];
            case 2 == $ps:
                return [
                    'result' => false,
                    'url' => $this->getOrderLinks()['cancel'],
                    'message' => 'Order has been cancelled on PayPlug page',
                ];
            default:
                break;
        }

        // ...then check if an order exists this related cart before redirect user
        $id_order = $this->dependencies
            ->getPlugin()
            ->getOrder()
            ->getIdByCartId((int) $context->cart->id);
        if ($id_order) {
            return [
                'result' => true,
                'url' => $this->getOrderLinks($id_order)['confirm'],
                'message' => 'Redirecting to order-confirmation page',
            ];
        }

        // ... then create order form cart id
        $order_create = $this->createOrder((int) $context->cart->id);

        if (!$order_create['result']) {
            return [
                'result' => false,
                'url' => $this->getOrderLinks()['error'],
                'message' => 'No stored payment get from given id cart.',
            ];
        }

        // If an order has beed created,  return confirmation link
        if (isset($order_create['id_order'])) {
            return [
                'result' => true,
                'url' => $this->getOrderLinks((int) $order_create['id_order'])['confirm'],
                'message' => 'Redirecting to order-confirmation page.',
            ];
        }

        // else show the validation template
        return [
            'result' => true,
            'message' => 'Show the validation template.',
        ];
    }
}
