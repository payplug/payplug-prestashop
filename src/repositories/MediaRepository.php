<?php

namespace PayPlug\src\repositories;

class MediaRepository extends Repository
{
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

        $this->smarty->assign([
            'messages' => $formated,
            'with_msg_button' => $with_msg_button
        ]);

        return $this->display(__FILE__, '_partials/messages.tpl');
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
            $this->context->smarty->assign([
                'sandbox' => $args['sandbox'],
                'embedded' => $args['embedded'],
                'one_click' => $args['one_click'],
                'oney' => $args['oney'],
                'installment' => $args['installment'],
                'deferred' => $args['deferred'],
                'activate' => $args['activate'],
            ]);
        }

        $admin_ajax_url = $this->getAdminAjaxUrl();

        $inst_id = isset($args['inst_id']) ? $args['inst_id'] : null;

        switch ($type) {
            case 'pwd':
                $title = $this->l('LIVE mode');
                break;
            case 'activate':
                $title = $this->l('LIVE mode');
                break;
            case 'premium':
                $title = $this->l('Enable advanced feature');
                break;
            case 'confirm':
                $title = $this->l('Save settings');
                break;
            case 'deactivate':
                $title = $this->l('Deactivate');
                break;
            case 'refund':
                $title = $this->l('Refund');
                break;
            case 'abort':
                $title = $this->l('Suspend installment');
                break;
            default:
                $title = '';
        }

        $this->context->smarty->assign([
            'title' => $title,
            'type' => $type,
            'admin_ajax_url' => $admin_ajax_url,
            'site_url' => $this->site_url,
            'inst_id' => $inst_id,
        ]);
        $this->html = $this->fetchTemplateRC('/views/templates/admin/popin.tpl');

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
        $output = $this->display(__FILE__, $file);
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
        return true;
    }
}