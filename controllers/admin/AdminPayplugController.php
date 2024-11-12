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
 *  @author    Payplug SAS
 *  @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */
require_once dirname(__FILE__) . '/../../vendor/autoload.php';

use PayPlug\classes\DependenciesClass;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminPayplugController extends ModuleAdminController
{
    private $dependencies;
    private $api_rest;
    private $constant;
    private $media;
    private $tools;

    public function __construct()
    {
        $this->bootstrap = true;

        parent::__construct();

        $this->dependencies = new DependenciesClass();
        $this->api_rest = $this->dependencies->getPlugin()->getApiRestClass();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->media = $this->dependencies->getPlugin()->getMedia();
        $this->tools = $this->dependencies->getPlugin()->getTools();

        // If referer is from development server, trigger api rest renderer
        if (isset($_SERVER['HTTP_REFERER']) && null != strpos($_SERVER['HTTP_REFERER'], 'localhost')) {
            $this->renderApiRest();
        }
    }

    /**
     * Initialize the content by adding Boostrap and loading the TPL.
     */
    public function initContent()
    {
        if ($session = $this->tools->tool('getValue', 'session')
            && $company_id = $this->tools->tool('getValue', '$company_id')) {
            $merchant = $this->module
                ->get('payplug.models.classes.merchant');
            $client_data = $merchant->getClientData($session, $company_id);
            if ($client_data['result']) {
                $merchant->registerClientData($client_data['data']);
            }
        }

        $this->renderApiRest();

        if ($this->tools->tool('getValue', '_ajax')) {
            if ($this->tools->tool('getValue', 'refund')) {
                $amount = str_replace(',', '.', $this->tools->tool('getValue', 'amount'));
                $amount_formated = is_numeric($amount)
                    ? $this->dependencies->amountCurrencyClass->convertAmount($amount)
                    : 0;
                $resource_id = $this->tools->tool('getValue', 'resource_id');
                $id_customer = $this->tools->tool('getValue', 'id_customer');
                $id_order = $this->tools->tool('getValue', 'id_order');
                $update_order_state = $this->tools->tool('getValue', 'update_order_state');
                $refund = $this->dependencies
                    ->getPlugin()
                    ->getPaymentAction()
                    ->refundAction(
                        (string) $resource_id,
                        (int) $amount_formated,
                        (int) $id_customer,
                        (int) $id_order,
                        (bool) $update_order_state
                    );

                exit(json_encode($refund));
            }
            if ($this->tools->tool('getValue', 'capture')) {
                $resource_id = $this->tools->tool('getValue', 'pay_id');
                $order_id = $this->tools->tool('getValue', 'id_order');
                $capture = $this->dependencies
                    ->getPlugin()
                    ->getPaymentAction()
                    ->captureAction($resource_id, (int) $order_id);

                if (!$capture['result']) {
                    exit(json_encode([
                        'status' => 'error',
                        'data' => $this->dependencies
                            ->getPlugin()
                            ->getTranslationClass()
                            ->l('payplug.capturePayment.cannotCapture', 'adminpayplugcontroller'),
                        'message' => $capture['message'],
                    ]));
                }

                exit(json_encode([
                    'status' => 'ok',
                    'data' => '',
                    'message' => $this->dependencies
                        ->getPlugin()
                        ->getTranslationClass()
                        ->l('payplug.capturePayment.captured', 'adminpayplugcontroller'),
                    'reload' => true,
                ]));
            }
            if ($this->tools->tool('getValue', 'confirmAbort')) {
                $inst_id = $this->tools->tool('getValue', 'inst_id');
                $this->dependencies->mediaClass->displayPopin('abort', ['inst_id' => $inst_id]);
            }
            if ($this->tools->tool('getValue', 'abort')) {
                $inst_id = $this->tools->tool('getValue', 'inst_id');
                $id_order = $this->tools->tool('getValue', 'id_order');
                $abort = $this->dependencies
                    ->getPlugin()
                    ->getPaymentAction()
                    ->abortAction($inst_id, (int) $id_order);

                if (!$abort['result']) {
                    exit(json_encode([
                        'status' => 'error',
                        'data' => $this->dependencies
                            ->getPlugin()
                            ->getTranslationClass()
                            ->l('payplug.abortPayment.cannotAbort', 'adminpayplugcontroller'),
                    ]));
                }

                exit(json_encode(['reload' => true]));
            }

            if ($this->tools->tool('getValue', 'update')) {
                $pay_id = $this->tools->tool('getValue', 'pay_id');
                $id_order = $this->tools->tool('getValue', 'id_order');
                $this->dependencies->paymentClass->updatePayment($pay_id, $id_order);
            }
        }

        $this->context->smarty->assign([
            'module_name' => $this->dependencies->name,
        ]);

        $lib_path = $this->constant->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/';
        $this->media->addJsDef([
            'payplug_admin_config' => [
                'ajax_url' => $this->dependencies->adminClass->getAdminAjaxUrl() . '&_ajax=1',
                'img_path' => $lib_path,
            ],
        ]);

        $this->context->smarty->assign([
            'lib_url' => $this->context->shop->getBaseURL(true) . 'modules/' . $this->dependencies->name . '/views/',
        ]);

        $this->context->controller->addCSS($lib_path . '/css/app.css');

        $this->content = $this->context->smarty->fetch($this->module->getLocalPath() . '/views/templates/admin/admin.tpl');

        parent::initContent();
    }

    /**
     * @description Render Api Rest Json
     */
    public function renderApiRest()
    {
        if ($rest_route = $this->tools->tool('getValue', 'rest_route')) {
            $json = $this->api_rest->dispatch($rest_route);
            exit(json_encode($json));
        }
    }
}
