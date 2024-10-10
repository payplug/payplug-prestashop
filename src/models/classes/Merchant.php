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

use PayPlug\classes\DependenciesClass;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Merchant
{
    public $dependencies;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
    }

    /**
     * @description Register client data given getted from API
     *
     * @param array $client_data
     *
     * @return bool
     */
    public function registerClientData($client_data = [])
    {
        if (!is_array($client_data) || empty($client_data)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Merchant::registerClientData - Invalid argument, $client_data must be an array.', 'error');

            return false;
        }

        $configuration = $this->dependencies
            ->getPlugin()
            ->getConfigurationClass();

        $register = isset($client_data['client_id'])
            && is_string($client_data['client_id'])
            && $client_data['client_id']
            && $configuration->set('client_id', $client_data['client_id']);

        return $register
            && isset($client_data['client_secret'])
            && is_string($client_data['client_secret'])
            && $client_data['client_secret']
            && $configuration->set('client_secret', $client_data['client_secret']);
    }
}
