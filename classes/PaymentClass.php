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

namespace PayPlug\classes;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PaymentClass
{
    private $assign;
    private $configuration;
    private $dependencies;
    private $logger;
    private $oney;
    private $order;
    private $orderHistory;
    private $validate;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;

        $this->assign = $this->dependencies->getPlugin()->getAssign();
        $this->configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $this->logger = $this->dependencies->getPlugin()->getLogger();
        $this->oney = $this->dependencies->getPlugin()->getOney();
        $this->order = $this->dependencies->getPlugin()->getOrder();
        $this->orderHistory = $this->dependencies->getPlugin()->getOrderHistory();
        $this->validate = $this->dependencies->getPlugin()->getValidate();
    }

    /**
     * @description update payment ressource
     *
     * @param $resource_id
     * @param $order_id
     */
    public function updatePayment($resource_id, $order_id)
    {
        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('resource_id', $resource_id);
        $retrieve = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod($stored_resource['method'])
            ->retrieve($stored_resource['resource_id']);

        if (!$retrieve['result']) {
            exit(json_encode([
                'data' => $this->dependencies
                    ->getPlugin()
                    ->getTranslationClass()
                    ->l('payplug.adminPayplugController.errorOccurred', 'paymentclass'),
                'status' => 'error',
            ]));
        }
        $payment = $retrieve['resource'];

        $state_addons = ($payment->is_live ? '' : '_test');
        if ((bool) $payment->is_paid) {
            $new_state = (int) $this->configuration->getValue('order_state_paid' . $state_addons);
        } elseif ((bool) $payment->is_live) {
            $new_state = (int) $this->configuration->getValue('order_state_error');
        } else {
            $new_state = (int) $this->configuration->getValue('order_state_error_test');
        }

        $order = $this->order->get((int) $order_id);

        if ($this->validate->validate('isLoadedObject', $order)) {
            $current_state = (int) $order->getCurrentState();
            if (0 != $current_state && $current_state != $new_state) {
                $history = $this->orderHistory->get();
                $history->id_order = (int) $order->id;
                $history->changeIdOrderState($new_state, (int) $order->id, true);
                $history->addWithemail();
                $this->logger->addLog('Change order state to ' . $new_state, 'notice');
            }
        }

        exit(json_encode([
            'message' => $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.adminPayplugController.orderUpdated', 'paymentclass'),
            'reload' => true,
        ]));
    }
}
