<?php
/**
 * 2013 - 2021 PayPlug SAS
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
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\classes;

class MediaClass extends \Payplug
{
    protected $context;
    private $payplug;

    public function __construct($payplug)
    {
        $this->payplug = $payplug;
        $this->context = \Context::getContext();
    }

    /**
     * Include css in template
     *
     * @param string $css_uri
     * @param string $css_media_type
     * @return void
     */
    public function addCSSRC($css_uri, $css_media_type = 'all')
    {
        $this->context->controller->addCSS($css_uri, $css_media_type);
    }

    /**
     * Include js script in template
     *
     * @param string $js_uri
     * @return void
     */
    public function addJsRC($js_uri)
    {
        $this->context->controller->addJS($js_uri);
    }

    /**
     * Display messages template
     *
     * @param array $messages
     * @param bool $with_msg_button
     * @return bool|string
     */
    public function displayMessages($messages = [], $with_msg_button = false)
    {
        if (empty($messages)) {
            return false;
        }

        $formated = [];
        foreach ($messages as $message) {
            $formated[] = [
                'type' => 'string',
                'value' => $message
            ];
        }

        $this->context->smarty->assign([
            'messages' => $formated,
            'with_msg_button' => $with_msg_button
        ]);

        return $this->payplug->fetchTemplate('_partials/messages.tpl');
    }

    /**
     * Display the right pop-in
     *
     * @param string $type
     * @param array $args
     * @return string
     */
    public function displayPopin($type, $args = null)
    {
        if ($type == 'confirm') {
            $has_payment = false;
            foreach ($args as $key => $arg) {
                if (in_array($key, ['standard', 'oney', 'installment']) && !$has_payment) {
                    $has_payment = $arg;
                }
            }

            $this->context->smarty->assign([
                'sandbox' => $args['sandbox'],
                'embedded' => $args['embedded'],
                'standard' => $args['standard'],
                'one_click' => $args['standard'] && $args['one_click'],
                'oney' => $args['oney'],
                'installment' => $args['installment'],
                'deferred' => $args['deferred'],
                'activate' => $args['activate'],
                'has_payment' => $has_payment,
            ]);
        }

        $admin_ajax_url = AdminClass::getAdminAjaxUrl();

        $inst_id = isset($args['inst_id']) ? $args['inst_id'] : null;

        switch ($type) {
            case 'pwd':
            case 'activate':
                $title = $this->payplug->l('payplug.displayPopin.liveMode');
                break;
            case 'premium':
                $title = $this->payplug->l('payplug.displayPopin.enableFeature');
                break;
            case 'oneyPremium':
                $title = $this->payplug->l('payplug.displayPopin.enableFeature');
                break;
            case 'confirm':
                $title = $this->payplug->l('payplug.displayPopin.saveSettings');
                break;
            case 'deactivate':
                $title = $this->payplug->l('payplug.displayPopin.deactivate');
                break;
            case 'refund':
                $title = $this->payplug->l('payplug.displayPopin.refund');
                break;
            case 'abort':
                $title = $this->payplug->l('payplug.displayPopin.suspendInstallment');
                break;
            case 'deferred':
                $title = $this->payplug->l('payplug.displayPopin.deferred');
                break;
            default:
                $title = '';
                break;
        }

        $this->context->smarty->assign([
            'title' => $title,
            'type' => $type,
            'admin_ajax_url' => $admin_ajax_url,
            'site_url' => $this->payplug->apiClass->getSiteUrl(),
            'inst_id' => $inst_id,
        ]);
        $this->html = $this->payplug->fetchTemplate('/views/templates/admin/popin.tpl');

        die(json_encode(['content' => $this->html]));
    }

    /**
     * Fetch smarty template
     *
     * @param string $file
     * @return string
     */
    public function fetchTemplateRC($file)
    {
        $output = $this->payplug->fetchTemplate($file);
        return $output;
    }

    /**
     * @description To load JS and CSS medias
     *
     * @param array|string $medias
     * @return bool
     */
    public function setMedia($medias)
    {
        if (!$medias) {
            return false;
        }

        if (!is_array($medias)) {
            $medias = [$medias];
        }

        foreach ($medias as $media) {
            if (strpos($media, 'css') === false) {
                $this->context->controller->addJS($media);
            } else {
                $this->context->controller->addCSS($media);
            }
        }
//        exit;
        return true;
    }
}
