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
     * @param int $cart_id
     * @param bool $last_try
     *
     * @return array
     */
    public function checkAction($cart_id = 0, $last_try = false)
    {
        if (!is_int($cart_id) || !$cart_id) {
            return [
                'result' => false,
                'action' => 'redirect',
                'redirected_url' => $this->getOrderLinks()['error'],
            ];
        }

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

        // Check if an order exists this related cart
        $id_order = $this->dependencies
            ->getPlugin()
            ->getOrder()
            ->getIdByCartId((int) $cart_id);
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
            ->updateBy('id_cart', (int) $cart_id, $fields);
        if (!$update) {
            return [
                'result' => false,
                'action' => 'redirect',
                'redirected_url' => $this->getOrderLinks()['error'],
            ];
        }

        // Then create order before redirect user
        $order_create = $this->createOrder((int) $cart_id);

        // if result is false an error has been occured
        if (!$order_create['result']) {
            return [
                'result' => false,
                'action' => 'redirect',
                'redirected_url' => $this->getOrderLinks()['error'],
            ];
        }

        // Clear lock before rendering it
        $this->clearLock((int) $cart_id);

        return [
            'result' => true,
            'action' => 'redirect',
            'redirected_url' => $this->getOrderLinks((int) $order_create['id_order'])['confirm'],
        ];
    }

    /**
     * @description Clear related lock
     *
     * @param int $cart_id
     *
     * @return bool
     */
    public function clearLock($cart_id = 0)
    {
        if (!is_int($cart_id) || !$cart_id) {
            return false;
        }

        if ($this->dependencies->configClass->isValidFeature('feature_queueing_system')) {
            return (bool) $this->dependencies
                ->getPlugin()
                ->getQueueAction()
                ->updateAction((int) $cart_id)['result'];
        }

        return $this->dependencies
            ->getPlugin()
            ->getLockRepository()
            ->deleteLock((int) $cart_id);
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
            ->getBy('id_cart', (int) $cart_id);
        if (empty($stored_payment)) {
            return [
                'result' => false,
            ];
        }

        // ...then if no lock is present to create order
        $locked_setted = $this->setLock((int) $cart_id, $stored_payment['resource_id']);
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
                'id_cart' => $order->id_cart,
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
     * @param int $cart_id
     * @param string $resource_id
     *
     * @return bool
     */
    public function setLock($cart_id = 0, $resource_id = '')
    {
        if (!is_int($cart_id) || !$cart_id) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Validation::setLock - $cart_id must be an non null integer', 'error');

            return false;
        }

        if (!is_string($resource_id) || !$resource_id) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Validation::setLock - $resource_id must be an non empty string', 'error');

            return false;
        }

        if ($this->dependencies->configClass->isValidFeature('feature_queueing_system')) {
            $exists = $this->dependencies
                ->getPlugin()
                ->getQueueRepository()
                ->getFirstNotTreatedEntry((int) $cart_id);

            if ($exists) {
                return false;
            }

            return (bool) $this->dependencies
                ->getPlugin()
                ->getQueueAction()
                ->hydrateAction((int) $cart_id, $resource_id)['result'];
        }

        return (bool) $this->dependencies->payplugLock->createLockG2((int) $cart_id, 'validation');
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

        // Get the related cart from given cart_id
        $cart = $this->dependencies
            ->getPlugin()
            ->getCart()
            ->get((int) $cart_id);

        if (!$this->dependencies
            ->getPlugin()
            ->getValidate()
            ->validate('isLoadedObject', $cart)) {
            return [
                'result' => false,
                'url' => $this->getOrderLinks()['error'],
                'message' => 'Given cart obj isn\'t a valid object.',
            ];
        }

        $context = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get();

        if ($context->customer->id != $cart->id_customer) {
            return [
                'result' => false,
                'url' => $this->getOrderLinks()['error'],
                'message' => 'Given cart customer id did not match with context customer id.',
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
            ->getIdByCartId((int) $cart->id);
        if ($id_order) {
            return [
                'result' => true,
                'url' => $this->getOrderLinks($id_order)['confirm'],
                'message' => 'Redirecting to order-confirmation page',
            ];
        }

        // ... then create order form cart id
        $order_create = $this->createOrder((int) $cart->id);

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
