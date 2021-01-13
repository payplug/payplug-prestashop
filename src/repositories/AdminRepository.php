<?php

namespace PayPlug\src\repositories;

class AdminRepository extends Repository
{
    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function adminAjaxController()
    {
        if (!Tools::getValue('_ajax', false)) {
            return;
        }

        if (Tools::getValue('popin')) {
            $args = null;
            if (Tools::getValue('type') == 'confirm') {
                $keys = [
                    'sandbox',
                    'embedded',
                    'one_click',
                    'oney',
                    'installment',
                    'activate',
                    'deferred',
                ];
                $args = [];
                foreach ($keys as $key) {
                    $args[$key] = Tools::getValue($key);
                }
            }
            $this->displayPopin(Tools::getValue('type'), $args);
        }

        if (Tools::getValue('submitSettings')) {
            if (Tools::getValue('PAYPLUG_INST_MIN_AMOUNT') < 4) {
                $this->displayError($this->l('Settings not updated'));

                die(json_encode(['error' => $this->l('Settings not updated')]));
            } else {
                $this->saveConfiguration();

                $this->assignContentVar();
                $content = $this->fetchTemplateRC('/views/templates/admin/admin.tpl');

                $this->context->smarty->assign([
                    'title' => '',
                    'type' => 'save',
                ]);
                $popin = $this->fetchTemplateRC('/views/templates/admin/popin.tpl');

                die(json_encode(['popin' => $popin, 'content' => $content]));
            }
        }

        if (Tools::isSubmit('submitAccount')) {
            /*
             * We can't use $password = Tools::getValue('PAYPLUG_PASSWORD');
             * Because pwd with special chars don't work
             */
            $password = $_POST['PAYPLUG_PASSWORD'];
            $email = Tools::getValue('PAYPLUG_EMAIL');
            if (!Validate::isEmail($email) || !PayPlug\backward\PayPlugBackward::isPlaintextPassword($password)) {
                die(json_encode([
                    'content' => null,
                    'error' => $this->l('The email and/or password was not correct.')
                ]));
            }

            if ($this->login($email, $password)) {
                Configuration::updateValue('PAYPLUG_EMAIL', Tools::getValue('PAYPLUG_EMAIL'));
                Configuration::updateValue('PAYPLUG_SHOW', 1);

                $this->assignContentVar();
                $content = $this->fetchTemplateRC('/views/templates/admin/admin.tpl');

                die(json_encode(['content' => $content]));
            } else {
                die(json_encode([
                    'content' => null,
                    'error' => $this->l('The email and/or password was not correct.')
                ]));
            }
        }

        if (Tools::getValue('submitPwd')) {
            $password = Tools::getValue('password');
            if (!$password || !PayPlug\backward\PayPlugBackward::isPlaintextPassword($password)) {
                die(json_encode(['content' => null, 'error' => $this->l('The password you entered is invalid')]));
            }

            $email = Configuration::get('PAYPLUG_EMAIL');

            if ($this->login($email, $password)) {
                $api_key = Configuration::get('PAYPLUG_LIVE_API_KEY');
                if ((bool)$api_key) {
                    Configuration::updateValue('PAYPLUG_SANDBOX_MODE', 0);
                    $this->assignContentVar();
                    $content = $this->fetchTemplateRC('/views/templates/admin/admin.tpl');
                    die(json_encode(['content' => $content]));
                } else {
                    $this->context->smarty->assign([
                        'title' => '',
                        'type' => 'activate',
                    ]);
                    $popin = $this->fetchTemplateRC('/views/templates/admin/popin.tpl');
                    die(json_encode(['popin' => $popin]));
                }
            } else {
                die(json_encode([
                    'content' => null,
                    'error' => $this->l('The email and/or password was not correct.')
                ]));
            }


            $this->submitPopinPwd($password);
        }

        if (Tools::getValue('submit') == 'submitPopin_abort') {
            $this->abortPayment();
        }
        if ((int)Tools::getValue('check') == 1) {
            $content = $this->getCheckFieldset();
            die(json_encode(['content' => $content]));
        }
        if ((int)Tools::getValue('log') == 1) {
            $content = $this->getLogin();
            die(json_encode(['content' => $content]));
        }
        if ((int)Tools::getValue('checkPremium') == 1) {
            $api_key = Configuration::get('PAYPLUG_LIVE_API_KEY');
            $permissions = $this->getAccountPermissions($api_key);
            $return = [
                'payplug_sandbox' => $permissions['use_live_mode'],
                'payplug_one_click' => $permissions['can_save_cards'],
                'payplug_oney' => $permissions['can_use_oney'],
                'payplug_inst' => $permissions['can_create_installment_plan'],
                'payplug_deferred' => $permissions['can_create_deferred_payment'],
            ];
            die(json_encode($return));
        }
        if (Tools::getValue('has_live_key')) {
            die(json_encode(['result' => $this->hasLiveKey()]));
        }
        if ((int)Tools::getValue('refund') == 1) {
            $this->refundPayment();
        }
        if ((int)Tools::getValue('capture') == 1) {
            $this->capturePayment();
        }
        if ((int)Tools::getValue('popinRefund') == 1) {
            $popin = $this->displayPopin('refund');
            die(json_encode(['content' => $popin]));
        }
        if ((int)Tools::getValue('update') == 1) {
            $pay_id = Tools::getValue('pay_id');
            $payment = $this->retrievePayment($pay_id);
            $id_order = Tools::getValue('id_order');

            if ((int)$payment->is_paid == 1) {
                if ($payment->is_live == 1) {
                    $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PAID');
                } else {
                    $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PAID_TEST');
                }
            } elseif ((int)$payment->is_paid == 0) {
                if ($payment->is_live == 1) {
                    $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_ERROR');
                } else {
                    $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_ERROR_TEST');
                }
            }

            $order = new Order((int)$id_order);
            if (Validate::isLoadedObject($order)) {
                $current_state = (int)$order->getCurrentState();
                if ($current_state != 0 && $current_state != $new_state) {
                    $history = new OrderHistory();
                    $history->id_order = (int)$order->id;
                    $history->changeIdOrderState($new_state, (int)$order->id);
                    $history->addWithemail();
                }
            }

            die(json_encode([
                'message' => $this->l('Order successfully updated.'),
                'reload' => true
            ]));
        }
    }

    /**
     * @description submit password
     *
     * @param string $pwd
     * @return string
     */
    public function submitPopinPwd($pwd)
    {
        $email = Configuration::get('PAYPLUG_EMAIL');
        $connected = $this->login($email, $pwd);
        $use_live_mode = false;

        if ($connected) {
            if (Configuration::get('PAYPLUG_LIVE_API_KEY') != '') {
                $use_live_mode = true;

                $valid_key = Configuration::get('PAYPLUG_LIVE_API_KEY');
                $permissions = $this->getAccount($valid_key);
                $can_save_cards = $permissions['can_save_cards'];
                $can_create_installment_plan = $permissions['can_create_installment_plan'];
            }
        } else {
            die(json_encode(['content' => 'wrong_pwd']));
        }
        if (!$use_live_mode) {
            die(json_encode(['content' => 'activate']));
        } elseif ($can_save_cards && $can_create_installment_plan) {
            die(json_encode(['content' => 'live_ok']));
        } elseif ($can_save_cards && !$can_create_installment_plan) {
            die(json_encode(['content' => 'live_ok_no_inst']));
        } elseif (!$can_save_cards && $can_create_installment_plan) {
            die(json_encode(['content' => 'live_ok_no_oneclick']));
        } else {
            die(json_encode(['content' => 'live_ok_not_premium']));
        }
    }

    /**
     * @description Read API response and return permissions
     *
     * @param string $json_answer
     * @return array OR bool
     */
    private function treatAccountResponse($json_answer, $is_sandbox = true)
    {
        if ((isset($json_answer['object']) && $json_answer['object'] == 'error')
            || empty($json_answer)
        ) {
            return false;
        }

        $id = $json_answer['id'];

        $configuration = [
            'currencies' => Configuration::get('PAYPLUG_CURRENCIES'),
            'min_amounts' => Configuration::get('PAYPLUG_MIN_AMOUNTS'),
            'max_amounts' => Configuration::get('PAYPLUG_MAX_AMOUNTS'),
            'oney_allowed_countries' => Configuration::get('PAYPLUG_ONEY_ALLOWED_COUNTRIES'),
            'oney_max_amounts' => Configuration::get('PAYPLUG_ONEY_MAX_AMOUNTS'),
            'oney_min_amounts' => Configuration::get('PAYPLUG_ONEY_MIN_AMOUNTS'),
        ];

        if (isset($json_answer['configuration'])) {
            if (isset($json_answer['configuration']['currencies'])
                && !empty($json_answer['configuration']['currencies'])) {
                $configuration['currencies'] = [];
                foreach ($json_answer['configuration']['currencies'] as $value) {
                    $configuration['currencies'][] = $value;
                }
            }

            if (isset($json_answer['configuration']['min_amounts'])
                && !empty($json_answer['configuration']['min_amounts'])) {
                $configuration['min_amounts'] = '';
                foreach ($json_answer['configuration']['min_amounts'] as $key => $value) {
                    $configuration['min_amounts'] .= $key . ':' . $value . ';';
                }
                $configuration['min_amounts'] = Tools::substr($configuration['min_amounts'], 0, -1);
            }

            if (isset($json_answer['configuration']['max_amounts'])
                && !empty($json_answer['configuration']['max_amounts'])) {
                $configuration['max_amounts'] = '';
                foreach ($json_answer['configuration']['max_amounts'] as $key => $value) {
                    $configuration['max_amounts'] .= $key . ':' . $value . ';';
                }
                $configuration['max_amounts'] = Tools::substr($configuration['max_amounts'], 0, -1);
            }

            if (isset($json_answer['configuration']['oney'])) {
                if (isset($json_answer['configuration']['oney']['allowed_countries'])
                    && !empty($json_answer['configuration']['oney']['allowed_countries'])
                    && sizeof($json_answer['configuration']['oney']['allowed_countries'])
                ) {
                    $allowed = '';
                    foreach ($json_answer['configuration']['oney']['allowed_countries'] as $country) {
                        $allowed .= $country . ',';
                    }
                    $configuration['oney_allowed_countries'] = Tools::substr($allowed, 0, -1);
                }

                if (isset($json_answer['configuration']['oney']['min_amounts'])
                    && !empty($json_answer['configuration']['oney']['min_amounts'])
                ) {
                    $configuration['oney_min_amounts'] = '';
                    foreach ($json_answer['configuration']['oney']['min_amounts'] as $key => $value) {
                        $configuration['oney_min_amounts'] .= $key . ':' . $value . ';';
                    }
                    $configuration['oney_min_amounts'] = Tools::substr($configuration['oney_min_amounts'], 0, -1);
                }

                if (isset($json_answer['configuration']['oney']['max_amounts'])
                    && !empty($json_answer['configuration']['oney']['max_amounts'])
                ) {
                    $configuration['oney_max_amounts'] = '';
                    foreach ($json_answer['configuration']['oney']['max_amounts'] as $key => $value) {
                        $configuration['oney_max_amounts'] .= $key . ':' . $value . ';';
                    }
                    $configuration['oney_max_amounts'] = Tools::substr($configuration['oney_max_amounts'], 0, -1);
                }
            }
        }

        $permissions = [
            'use_live_mode' => $json_answer['permissions']['use_live_mode'],
            'can_save_cards' => $json_answer['permissions']['can_save_cards'],
            'can_create_installment_plan' => $json_answer['permissions']['can_create_installment_plan'],
            'can_create_deferred_payment' => $json_answer['permissions']['can_create_deferred_payment'],
            'can_use_oney' => $json_answer['permissions']['can_use_oney'],
        ];

        // If sandbox mode active, no allowed countries sent
        // Then set default as `FR,MQ,YT,RE,GF,GP,IT`
        if (isset($json_answer['is_live']) && !$json_answer['is_live']) {
            $configuration['oney_allowed_countries'] = 'FR,MQ,YT,RE,GF,GP,IT';
        }

        Configuration::updateValue('PAYPLUG_COMPANY_ID' . ($is_sandbox ? '_TEST' : ''), $id);
        Configuration::updateValue('PAYPLUG_CURRENCIES', implode(';', $configuration['currencies']));
        Configuration::updateValue('PAYPLUG_MIN_AMOUNTS', $configuration['min_amounts']);
        Configuration::updateValue('PAYPLUG_MAX_AMOUNTS', $configuration['max_amounts']);
        Configuration::updateValue('PAYPLUG_ONEY_ALLOWED_COUNTRIES', $configuration['oney_allowed_countries']);
        Configuration::updateValue('PAYPLUG_ONEY_MAX_AMOUNTS', $configuration['oney_max_amounts']);
        Configuration::updateValue('PAYPLUG_ONEY_MIN_AMOUNTS', $configuration['oney_min_amounts']);

        return $permissions;
    }

    /**
     * @param string $controller_name
     * @param int $id_order
     * @return string
     */
    public function getAdminAjaxUrl($controller_name = 'AdminModules', $id_order = 0)
    {
        if ($controller_name == 'AdminModules') {
            $admin_ajax_url = 'index.php?controller=' . $controller_name . '&configure=' . $this->name
                . '&tab_module=payments_gateways&module_name=payplug&token=' .
                Tools::getAdminTokenLite($controller_name);
        } elseif ($controller_name == 'AdminOrders') {
            $admin_ajax_url = 'index.php?controller=' . $controller_name . '&id_order=' . $id_order
                . '&vieworder&token=' . Tools::getAdminTokenLite($controller_name);
        }
        return $admin_ajax_url;
    }

    /**
     * @description
     * Get account permission from Payplug API
     *
     * @param string $api_key
     * @param boolean $sandbox
     * @return array OR bool
     */
    public function getAccount($api_key, $sandbox = true)
    {
        $this->setSecretKey($api_key);
        $response = \Payplug\Authentication::getAccount();
        $json_answer = $response['httpResponse'];
        if ($permissions = $this->treatAccountResponse($json_answer, $sandbox)) {
            return $permissions;
        } else {
            return false;
        }
    }

    /**
     * @description
     * Check if account is premium
     *
     * @param string $api_key
     * @return bool
     */
    public function getAccountPermissions($api_key = null)
    {
        if ($api_key == null) {
            $api_key = self::setAPIKey();
        }
        $permissions = $this->getAccount($api_key, false);
        return $permissions;
    }

    /**
     * login to Payplug API
     *
     * @param string $email
     * @param string $password
     * @return bool
     * @throws \Payplug\Exception\BadRequestException
     */
    private function login($email, $password)
    {
        try {
            $response = \Payplug\Authentication::getKeysByLogin($email, $password);

            $json_answer = $response['httpResponse'];
            if ($this->setApiKeysbyJsonResponse($json_answer)) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            json_encode([
                'content' => null,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * @return string
     * @see Module::getContent()
     *
     */
    public function getContent()
    {
        if (Tools::getValue('_ajax')) {
            $this->adminAjaxController();
        }

        $this->postProcess();

        $this->assignContentVar();

        $this->html .= $this->fetchTemplateRC('/views/templates/admin/admin.tpl');

        return $this->html;
    }

    public function getLogin()
    {
        $this->postProcess();

        $this->assignContentVar();

        $this->html = $this->fetchTemplateRC('/views/templates/admin/login.tpl');

        return $this->html;
    }

}