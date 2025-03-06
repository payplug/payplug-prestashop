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
    public $display_column_left;
    private $cart_id;
    private $ps;
    private $dependencies;
    private $logger;

    public function __construct()
    {
        parent::__construct();
        $this->dependencies = new DependenciesClass();
        $this->logger = $this->dependencies->getPlugin()->getLogger();
    }

    public function postProcess()
    {
        $this->logger->addLog('ValidationController::postProcess - Start validation post processing');
        $this->ps = (int) $this->dependencies
            ->getPlugin()
            ->getTools()
            ->tool('getValue', 'ps');
        $this->logger->addLog('ValidationController::postProcess - Given ps argument: ' . $this->ps);
        $this->cart_id = (int) $this->dependencies
            ->getPlugin()
            ->getTools()
            ->tool('getValue', 'cartid');
        $this->logger->addLog('ValidationController::postProcess - Given cart_id argument: ' . $this->cart_id);

        if ($this->dependencies
            ->getPlugin()
            ->getTools()
            ->tool('getValue', '_ajax')) {
            $last_try = $this->dependencies
                ->getPlugin()
                ->getTools()
                ->tool('getValue', 'last_try');

            $this->logger->addLog('ValidationController::postProcess - is last try: ' . ($last_try ? 'yes' : 'no'));
            $check = $this->dependencies
                ->getPlugin()
                ->getValidationAction()
                ->checkAction((int) $this->cart_id, (bool) $last_try);
            exit(json_encode($check));
        }

        $order_validate = $this->dependencies
            ->getPlugin()
            ->getValidationAction()
            ->validateAction($this->ps, $this->cart_id);

        $error_message = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->l('The transaction was not completed and your card was not charged.', 'validation');

        if (!$order_validate['result']) {
            $this->logger->addLog('ValidationController::postProcess - An error occured: ' . $order_validate['message']);
            $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie([$error_message]);
            $this->exitProcess($order_validate['url'], $order_validate['message']);
        }

        if (isset($order_validate['url'])) {
            $this->logger->addLog('ValidationController::postProcess - Order url getted, redirect on: ' . $order_validate['url']);
            $this->exitProcess($order_validate['url'], $order_validate['message']);
        }

        $this->logger->addLog('ValidationController::postProcess - No redirection reach during the postprocess treatment');
    }

    /**
     * @see FrontController::initContent()
     *
     * @todo move in ValidationAction::loaderRender()
     */
    public function initContent()
    {
        $this->logger->addLog('ValidationController::initContent - Start initContent');

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
                    'ajax_cart_id' => $this->cart_id,
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
            $this->logger->addLog($message);
        }

        // Remove lock
        $this->dependencies
            ->getPlugin()
            ->getValidationAction()
            ->clearLock($this->cart_id);

        $this->dependencies
            ->getPlugin()
            ->getTools()
            ->tool('redirect', $url);
    }
}
