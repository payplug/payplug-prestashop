<?php
/**
 * 2013 - 2023 PayPlug SAS
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
 * @author    PayPlug SAS
 * @copyright 2013 - 2023 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\actions;

class ConfigurationAction
{
    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    public function loginAction($datas = null)
    {
        $logger = $this->dependencies->getPlugin()->getLogger();

        if (!is_object($datas) || !$datas) {
            $logger->addLog('ConfigurationAction::loginAction: Invalid parameter given, $datas must be a non empty object.');

            return [
                'success' => false,
                'data' => [
                    // @todo: add translation
                    'message' => 'An error has occurred',
                ],
            ];
        }

        if (!isset($datas->action) || 'payplug_login' != $datas->action) {
            $logger->addLog('ConfigurationAction::loginAction: Invalid parameter given, $datas->action is invalid.');

            return [
                'success' => false,
                'data' => [
                    // @todo: add translation
                    'message' => 'An error has occurred',
                ],
            ];
        }

        $email = $datas->payplug_email;
        if (!$email) {
            $logger->addLog('ConfigurationAction::loginAction: invalid email.');

            return [
                'success' => false,
                'data' => [
                    // @todo: add translation
                    'message' => 'The email you entered is invalid.',
                ],
            ];
        }

        $password = $datas->payplug_password;
        $isPlaintextPassword = $this->dependencies->configClass->getAdapterPrestaClasse()->isPlaintextPassword($password);
        if (!$password || !$isPlaintextPassword) {
            $logger->addLog('ConfigurationAction::loginAction: invalid password.');

            return [
                'success' => false,
                'data' => [
                    // @todo: add translation
                    'message' => 'The password you entered is invalid.',
                ],
            ];
        }

        if (!$this->dependencies->apiClass->login($email, $password)) {
            $logger->addLog('ConfigurationAction::loginAction: invalid email and/or password.');

            return [
                'success' => false,
                'data' => [
                    // @todo: add translation
                    'message' => 'The email and/or password was not correct.',
                ],
            ];
        }

        $config = $this->dependencies->getPlugin()->getConfiguration();
        $config->updateValue($this->dependencies->getConfigurationKey('email'), $email);
        $config->updateValue($this->dependencies->getConfigurationKey('show'), 1);
        if ((bool) $config->get($this->dependencies->getConfigurationKey('liveApiKey'))) {
            $config->updateValue($this->dependencies->getConfigurationKey('sandboxMode'), 0);
        }

        return $this->renderConfiguration();
    }

    public function logoutAction()
    {
        $this->dependencies->configClass->logout();

        return $this->renderConfiguration();
    }

    public function renderConfiguration()
    {
        $api_rest = $this->dependencies->getPlugin()->getApiRest();
        $config = $this->dependencies->getPlugin()->getConfiguration();
        $payplug_email = $config->get($this->dependencies->getConfigurationKey('email'));

        $datas = [
            'success' => true,
            'data' => [
                'header' => $api_rest->getHeaderSection(),
                'status' => false,
            ],
        ];

        if ($payplug_email != '') {
            $datas['data']['payplug_wooc_settings'] = $api_rest->getDataFields($config);
            $datas['data']['settings'] = $api_rest->getSettingsSection(true);
            $datas['data']['login'] = $api_rest->getLoginSection();
            $datas['data']['logged'] = $api_rest->getLoggedSection();
        } else {
            $datas['data']['settings'] = $api_rest->getSettingsSection(false);
            $datas['data']['login'] = $api_rest->getLoginSection();
        }

        return $datas;
    }
}
