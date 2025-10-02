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

use PayPlug\classes\DependenciesClass;

if (!defined('_PS_VERSION_')) {
    exit;
}

class RequestAction
{
    public $dependencies;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
    }

    public function dispatchAction($method = '', $parameters = [])
    {
        if (!is_string($method) || !$method) {
            return [
                'result' => false,
                'message' => 'Invalid argument, $method must be a non empty string.',
            ];
        }

        if (!is_array($parameters)) {
            return [
                'result' => false,
                'message' => 'Invalid argument, $parameters must be a valid array.',
            ];
        }

        $method_name = $method . 'Action';
        if (!is_callable([$this, $method_name])) {
            return [
                'result' => false,
                'message' => 'Method not found in object RequestAction',
            ];
        }

        // If $parameters given return method using them.
        try {
            if (!empty($parameters)) {
                $return = $this->{$method_name}($parameters);
            } else {
                $return = $this->{$method_name}();
            }
        } catch (\Exception $exception) {
            $this->dependencies->getPlugin()
                ->getLogger()
                ->addLog('RequestAction::dispatchAction - Exception thrown: ' . $exception->getMessage(), 'error');

            $return = [
                'result' => false,
                'message' => 'Exception thrown: ' . $exception->getMessage(),
            ];
        }

        return $return;
    }

    /**
     * @description handle request to update applepay payment
     * @todo: add coverage to this method
     *
     * @return array
     */
    public function applepayUpdateAction()
    {
        $workflow = $this->dependencies
            ->getPlugin()
            ->getTools()
            ->tool('getValue', 'workflow');
        $request = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod('applepay')
            ->getRequest($workflow);

        return [
            'result' => is_array($request) && !empty($request),
            'request' => $request,
        ];
    }

    /**
     * @description handle request to cancel applepay payment
     * @todo: add coverage to this method
     *
     * @return array
     */
    public function applepayCancelAction()
    {
        return $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod('applepay')
            ->cancelPaymentResource();
    }

    /**
     * @description handle request to patch applepa payment
     * @todo: add coverage to this method
     *
     * @param $params
     *
     * @return array
     */
    public function applepayPatchAction($params = [])
    {
        if (!is_array($params) || empty($params)) {
            return [
                'result' => false,
                'message' => 'Invalid argument, $params must be a non empty array.',
            ];
        }

        $resource_id = isset($params['pay_id']) ? $params['pay_id'] : null;
        $token = isset($params['token']) ? $params['token'] : null;
        $workflow = isset($params['workflow']) ? $params['workflow'] : null;
        $carrier = isset($params['carrier']) ? $params['carrier'] : null;
        $user = isset($params['user']) ? $params['user'] : null;

        if (!is_string($resource_id) || !$resource_id) {
            return [
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ];
        }
        if (!is_array($token) || empty($token)) {
            return [
                'result' => false,
                'message' => 'Invalid argument, $token must be a non empty array.',
            ];
        }
        if (!is_string($workflow) || !$workflow) {
            return [
                'result' => false,
                'message' => 'Invalid argument, $workflow must be a non empty string.',
            ];
        }

        $patch = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod('applepay')
            ->patchPaymentResource($resource_id, $token, $workflow, $carrier, $user);

        if (!$patch['result']) {
            return $patch;
        }

        return [
            'result' => true,
            'return_url' => $patch['return_url'],
            'message' => 'Success',
        ];
    }
}
