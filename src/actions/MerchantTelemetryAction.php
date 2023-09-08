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

class MerchantTelemetryAction
{
    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    public function sendAction($source = '')
    {
        if (!$source || !is_string($source)) {
            // todo: add log ?
            return false;
        }

        $telemetries = $this->renderTelemetries($source);
        if (!$telemetries['result']) {
            // todo: add log ?
            return false;
        }

        if (!isset($telemetries['telemetries'])) {
            // todo: add log ?
            return true;
        }

        $api_key = $this->dependencies
            ->getPlugin()
            ->getConfigurationClass()
            ->getValue('live_api_key');

        $send = $this->dependencies
            ->getPlugin()
            ->getMerchantTelemetry()
            ->send($api_key, json_encode($telemetries['telemetries']));

        return $send['result'];
    }

    public function renderTelemetries($source = '')
    {
        if (!$source || !is_string($source)) {
            return [
                'result' => false,
                'message' => 'Invalid parameter given, $source must be a non empty string.',
            ];
        }

        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $module = $this->dependencies->getPlugin()->getModule()->getInstanceByName($this->dependencies->name);

        // We get the needed telemetries of the merchant platform
        $current_configurations = [];
        foreach ($configuration->getCurrentConfigurations() as $name => $value) {
            $current_configurations[] = [
                'name' => $name,
                'value' => $value,
            ];
        }
        $telemetries = [
            'version' => $module->version,
            'php_version' => $this->dependencies->getPlugin()->getConstant()->get('PHP_VERSION'),
            'name' => $this->dependencies->name,
            'configurations' => $current_configurations ?: [],
            'domains' => $this->dependencies->getPlugin()->getShopRepository()->getActiveShopUrl(),
            'modules' => $this->dependencies->getPlugin()->getModuleRepository()->getActiveModule(),
        ];

        // Clean of the configuration keys we don't want to send
        $protected_configurations_keys = [
            'company_id_test',
            'email',
            'live_api_key',
            'order_state_auth',
            'order_state_auth_test',
            'order_state_cancelled',
            'order_state_cancelled_test',
            'order_state_error',
            'order_state_error_test',
            'order_state_exp',
            'order_state_exp_test',
            'order_state_oney_pg',
            'order_state_oney_pg_test',
            'order_state_paid',
            'order_state_paid_test',
            'order_state_pending',
            'order_state_pending_test',
            'order_state_refund',
            'order_state_refund_test',
            'test_api_key',
            'telemetry_hash',
        ];
        foreach ($protected_configurations_keys as $key) {
            unset($telemetries['configurations'][$key]);
        }

        // Then through an hash, we check if the send of the datas is required
        $hash = hash('sha256', 'merchant_telemetry_' . json_encode($telemetries));

        // If the hash is the same, we don't continue the process
        if ($hash == $configuration->getValue('telemetry_hash')) {
            return [
                'result' => true,
                'message' => 'Current configuration correspond to previous hash.',
            ];
        }

        // Else, we update the new hash in database before implement the datas to send the value.
        $configuration->set('telemetry_hash', $hash);

        $telemetries['source'] = $source;

        return [
            'result' => true,
            'telemetries' => $telemetries,
        ];
    }
}
