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

namespace PayPlug\src\models\classes;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayplugUnifiedCore\Contracts\IOrderStateMutator;
use PayplugUnifiedCore\DataValues\PaymentOutcome;

class UpcOrderStateMutator implements IOrderStateMutator
{
    private $dependencies;
    private $logger;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->logger = new UpcLogger($dependencies);
    }

    public function apply(string $orderId, string $outcome): void
    {
        if (!PaymentOutcome::isValid($outcome)) {
            $this->logger->error('UpcOrderStateMutator::apply - Invalid outcome given: ' . $outcome);

            return;
        }

        $order = $this->dependencies->getPlugin()->getOrder()->get((int) $orderId);
        if (!$order || !(int) $order->id) {
            $this->logger->error('UpcOrderStateMutator::apply - Order not found for id: ' . $orderId);

            return;
        }

        $is_live = !(bool) $this->dependencies->getPlugin()->getConfigurationClass()->getValue('sandbox_mode');
        $order_states = $this->dependencies->getPlugin()->getOrderClass()->getOrderStates($is_live);
        $bucket = $this->bucketForOutcome($outcome);
        $new_order_state = isset($order_states[$bucket]) ? (int) $order_states[$bucket] : 0;

        if (!$new_order_state) {
            $this->logger->error('UpcOrderStateMutator::apply - No order state configured for bucket: ' . $bucket);

            return;
        }

        $this->dependencies->getPlugin()->getOrderClass()->updateOrderState($order, $new_order_state);
    }

    private function bucketForOutcome(string $outcome): string
    {
        switch ($outcome) {
            case PaymentOutcome::PAID:
                return 'paid';

            case PaymentOutcome::AUTHORIZED:
            case PaymentOutcome::CAPTURE_REQUIRED:
                return 'auth';

            case PaymentOutcome::THREE_DS_PENDING:
                return 'pending';

            case PaymentOutcome::REFUNDED:
                return 'refund';

            case PaymentOutcome::FAILED:
            default:
                return 'error';
        }
    }
}
