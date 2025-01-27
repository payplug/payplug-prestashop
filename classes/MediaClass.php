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

namespace PayPlug\classes;

use PayPlug\src\application\adapter\MediaAdapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

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
     * @param bool $with_msg_button
     * @param bool $with_yes_no_buttons
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
     * @param array $args
     *
     * @return string
     */
    public function displayPopin($type, $args = null)
    {
        $admin_ajax_url = $this->dependencies->adminClass->getAdminAjaxUrl();
        $inst_id = isset($args['inst_id']) ? $args['inst_id'] : null;
        $title = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->l('payplug.displayPopin.suspendInstallment', 'mediaclass');
        $this->context->smarty->assign([
            'title' => $title,
            'type' => $type,
            'admin_ajax_url' => $admin_ajax_url,
            'site_url' => $this->dependencies->getPlugin()->getApiService()->getSiteUrl(),
            'portal_url' => $this->dependencies->getPlugin()->getApiService()->getPortalUrl(),
            'inst_id' => $inst_id,
        ]);
        $html = $this->dependencies->configClass->fetchTemplate('/views/templates/admin/popin.tpl');
        exit(json_encode(['content' => $html]));
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
            if (false === strpos($media, 'css')) {
                $this->context->controller->addJS($media);
            } else {
                $this->context->controller->addCSS($media);
            }
        }

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
