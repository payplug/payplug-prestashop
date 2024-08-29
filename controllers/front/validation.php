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
 * International Registered Trademark & Property of Payplug SAS
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\classes\DependenciesClass;

class PayplugValidationModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
        $this->dependencies = new DependenciesClass();
    }

    public function postProcess()
    {
        if ($this->dependencies
            ->getPlugin()
            ->getTools()
            ->tool('getValue', '_ajax')) {
            $last_try = $this->dependencies
                ->getPlugin()
                ->getTools()
                ->tool('getValue', 'last_try');

            $check = $this->dependencies
                ->getPlugin()
                ->getValidationAction()
                ->checkAction((bool) $last_try);
            exit(json_encode($check));
        }

        $ps = (int) $this->dependencies
            ->getPlugin()
            ->getTools()
            ->tool('getValue', 'ps');
        $cart_id = (int) $this->dependencies
            ->getPlugin()
            ->getTools()
            ->tool('getValue', 'cartid');
        $order_validate = $this->dependencies
            ->getPlugin()
            ->getValidationAction()
            ->validateAction($ps, $cart_id);

        $error_message = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->l('validation.message.error', 'validation');

        if (!$order_validate['result']) {
            $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie([$error_message]);
            $this->exitProcess($order_validate['url'], $order_validate['message']);
        }

        if (isset($order_validate['url'])) {
            $this->exitProcess($order_validate['url'], $order_validate['message']);
        }
    }

    /**
     * @see FrontController::initContent()
     *
     * @todo move in ValidationAction::loaderRender()
     */
    public function initContent()
    {
        $this->display_column_left = false;
        parent::initContent();

        $validation_ajax_url = $this->context->link->getModuleLink(
            'payplug',
            'validation',
            [
                '_ajax' => 1,
            ],
            true
        );

        $this->dependencies
            ->getPlugin()
            ->getMedia()
            ->addJsDef(
                [
                    'validation_ajax_url' => $validation_ajax_url,
                ],
                true
            );
        $this->setTemplate('module:' . $this->dependencies->name . '/views/templates/front/validation.tpl');
    }

    /**
     * @description Unlock process and redirect
     *
     * @param string $url
     * @param string $message
     */
    private function exitProcess($url = '', $message = '')
    {
        if (is_string($message) && $message) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog($message);
        }

        // Remove lock
        $this->dependencies
            ->getPlugin()
            ->getValidationAction()
            ->clearLock();

        $this->dependencies
            ->getPlugin()
            ->getTools()
            ->tool('redirect', $url);
    }
}
