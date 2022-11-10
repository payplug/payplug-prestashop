<?php
/**
 * 2013 - 2022 PayPlug SAS
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
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\classes;

use PayPlug\src\application\adapter\MediaAdapter;

class MediaClass
{
    private $dependencies;
    private $context;
    private $media;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
        $this->media = $this->dependencies->getPlugin()->getMedia();
    }

    /**
     * @description  Include css in template
     *
     * @param string $css_uri
     * @param string $css_media_type
     */
    public function addCSSRC($css_uri, $css_media_type = 'all')
    {
        $this->context->controller->addCSS($css_uri, $css_media_type);
    }

    /**
     * @description  Include js script in template
     *
     * @param string $js_uri
     */
    public function addJsRC($js_uri)
    {
        $this->context->controller->addJS($js_uri);
    }

    /**
     * @description  Display messages template
     *
     * @param array $messages
     * @param bool  $with_msg_button
     * @param bool  $with_yes_no_buttons
     *
     * @return bool|string
     */
    public function displayMessages($messages = [], $with_msg_button = false, $with_yes_no_buttons = false)
    {
        if (empty($messages)) {
            return false;
        }

        $formated = [];
        foreach ($messages as $message) {
            $formated[] = [
                'type' => 'string',
                'value' => $message,
            ];
        }

        $this->context->smarty->assign([
            'messages' => $formated,
            'with_msg_button' => $with_msg_button,
            'with_yes_no_buttons' => $with_yes_no_buttons,
        ]);

        return $this->dependencies->configClass->fetchTemplate('_partials/messages.tpl');
    }

    /**
     * @description Display the right pop-in
     *
     * @param string $type
     * @param array  $args
     *
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
                'bancontact' => $args['bancontact'],
                'bancontact_feature' => $this->dependencies->configClass->isValidFeature('feature_bancontact'),
                'integrated_feature' => $this->dependencies->configClass->isValidFeature('feature_integrated'),
                'display_mode_feature' => $this->dependencies->configClass->isValidFeature('feature_display_mode'),
                'standard_feature' => $this->dependencies->configClass->isValidFeature('feature_standard'),
                'installment_feature' => $this->dependencies->configClass->isValidFeature('feature_installment'),
                'deferred_feature' => $this->dependencies->configClass->isValidFeature('feature_deferred'),
                'installment' => $args['installment'],
                'deferred' => $args['deferred'],
                'activate' => $args['activate'],
                'has_payment' => $has_payment,
            ]);
        }

        $admin_ajax_url = $this->dependencies->adminClass->getAdminAjaxUrl();

        $inst_id = isset($args['inst_id']) ? $args['inst_id'] : null;

        switch ($type) {
            case 'pwd':
            case 'activate':
                $title = $this->dependencies->l('payplug.displayPopin.liveMode', 'mediaclass');

                break;

            case 'premium':
            case 'confirm':
                $title = $this->dependencies->l('payplug.displayPopin.saveSettings', 'mediaclass');

                break;

            case 'deactivate':
                $title = $this->dependencies->l('payplug.displayPopin.deactivate', 'mediaclass');

                break;

            case 'refund':
                $title = $this->dependencies->l('payplug.displayPopin.refund', 'mediaclass');

                break;

            case 'abort':
                $title = $this->dependencies->l('payplug.displayPopin.suspendInstallment', 'mediaclass');

                break;

            case 'deferred':
                $title = $this->dependencies->l('payplug.displayPopin.deferred', 'mediaclass');

                break;

            default:
                $title = '';

                break;
        }

        foreach ($this->dependencies->configClass->features_json->features as $key => $value) {
            $this->context->smarty->assign([
                $value => $value,
            ]);
        }

        $this->context->smarty->assign([
            'title' => $title,
            'type' => $type,
            'admin_ajax_url' => $admin_ajax_url,
            'site_url' => $this->dependencies->apiClass->getSiteUrl(),
            'portal_url' => $this->dependencies->apiClass->getPortalUrl(),
            'inst_id' => $inst_id,
        ]);

        $this->html = $this->dependencies->configClass->fetchTemplate('/views/templates/admin/popin.tpl');

        exit(json_encode(['content' => $this->html]));
    }

    /**
     * @description  Fetch smarty template
     *
     * @param string $file
     *
     * @return string
     */
    public function fetchTemplateRC($file)
    {
        return $this->dependencies->configClass->fetchTemplate($file);
    }

    /**
     * @description To load JS and CSS medias
     *
     * @param array|string $medias
     *
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

    public function getMediaPath($path = false)
    {
        if (!$path) {
            return false;
        }

        return MediaAdapter::getMediaPath($path);
    }
}
