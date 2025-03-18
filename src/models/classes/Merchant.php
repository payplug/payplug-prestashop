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
     * @description Generate JWT
     *
     * @param array $client_datas
     *
     * @return array
     */
    public function generateJWT($client_datas = [])
    {
        if (!is_array($client_datas) || empty($client_datas)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Merchant::generateJWT - Invalid argument, $client_datas must be a non empty array.', 'error');

            return [
                'result' => false,
                'message' => 'Wrong $client_datas given',
            ];
        }

        $jwt = [];
        foreach ($client_datas as $key => $data) {
            if (empty($data)) {
                $jwt[$key] = [];

                continue;
            }

            $generated_jwt = $this->dependencies
                ->getPlugin()
                ->getModule()
                ->getInstanceByName($this->dependencies->name)
                ->getService('payplug.utilities.service.api')
                ->generateJWT($data['client_id'], $data['client_secret']);

            if (!$generated_jwt['result']) {
                return [
                    'result' => false,
                    'message' => 'Error during JWT generation',
                ];
            }

            $jwt[$key] = $generated_jwt['data'];
        }

        return [
            'result' => true,
            'data' => $jwt,
        ];
    }

    /**
     * @description Get client data for live and test mode
     *
     * @param string $session
     * @param string $company_id
     *
     * @return array
     */
    public function getClientData($session = '', $company_id = '')
    {
        if (!is_string($session) || empty($session)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Merchant::getClientData - Invalid argument, $session must be a non empty string.', 'error');

            return [
                'result' => false,
                'message' => 'Wrong session given',
            ];
        }

        if (!is_string($company_id) || empty($company_id)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Merchant::getClientData - Invalid argument, $company_id must be a non empty string.', 'error');

            return [
                'result' => false,
                'message' => 'Wrong company_id given',
            ];
        }

        $data = [];
        $client_name = 'Prestashop';

        // Get the client id and secret for test mode
        $client_data_test = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.service.api')
            ->getClientData($company_id, $client_name, 'test', $session);
        $data['test'] = $client_data_test['result'] ? [
            'client_id' => $client_data_test['data']['client_id'],
            'client_secret' => $client_data_test['data']['client_secret'],
        ] : [];

        // Get the client id and secret for live mode
        $client_data_live = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.service.api')
            ->getClientData($company_id, $client_name, 'live', $session);
        $data['live'] = $client_data_live['result'] ? [
            'client_id' => $client_data_live['data']['client_id'],
            'client_secret' => $client_data_live['data']['client_secret'],
        ] : [];

        return [
            'result' => true,
            'data' => $data,
        ];
    }

    /**
     * @description Register client data given got from API
     * todo: remove this method
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

        return $this->dependencies
            ->getPlugin()
            ->getConfigurationClass()
            ->set('client_data', json_encode($client_data));
    }

    /**
     * @description Register JWT got from API
     * todo: remove this method
     *
     * @param array $jwt
     *
     * @return bool
     */
    public function registerJWT($jwt = [])
    {
        if (!is_array($jwt) || empty($jwt)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Merchant::registerJWT - Invalid argument, jwt must be an array.', 'error');

            return false;
        }

        return $this->dependencies
            ->getPlugin()
            ->getConfigurationClass()
            ->set('jwt', json_encode($jwt));
    }
}
