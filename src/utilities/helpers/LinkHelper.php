<?php
/**
 * 2013 - 2026 Payplug SAS.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * @author    Payplug SAS
 * @copyright 2013 - 2026 Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */

namespace PayPlug\src\utilities\helpers;

if (!defined('_PS_VERSION_')) {
    exit;
}

class LinkHelper
{
    /**
     * Generate the OAuth callback URL.
     * PS >= 9.0.0: uses a front-office module controller.
     * PS < 9.0.0: uses the legacy admin link.
     *
     * @param \Context $context
     *
     * @return string
     */
    public static function getOAuthCallbackUrl($context)
    {
        if (version_compare(_PS_VERSION_, '9.0.0', '>=')) {
            // Store admin return URL only for PS9 and only in admin context
            self::storeAdminReturnUrl($context);
            $url = $context->link->getModuleLink('payplug', 'oauthcallback', [], true);

            // Strip any token params for a clean callback URL
            $parsed = parse_url($url);
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $params);
                unset($params['_token'], $params['token']);
                $cleanQuery = http_build_query($params);
                $url = $parsed['scheme'] . '://' . $parsed['host']
                    . (isset($parsed['port']) ? ':' . $parsed['port'] : '')
                    . $parsed['path']
                    . ($cleanQuery ? '?' . $cleanQuery : '');
            }

            return $url;
        }

        return $context->link->getAdminLink('AdminPayplug');
    }

    /**
     * Store the admin config page URL so the front-office callback can redirect back.
     * Only runs in admin context where _PS_ADMIN_DIR_ is defined.
     *
     * @param \Context $context
     */
    public static function storeAdminReturnUrl($context)
    {
        if (!defined('_PS_ADMIN_DIR_')) {
            return;
        }

        $adminUrl = null;

        // Try Symfony router first (available in admin context, future-proof)
        try {
            $container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
            if ($container) {
                $router = $container->get('router');
                $adminUrl = $router->generate(
                    'admin_module_configure_action',
                    ['module_name' => 'payplug'],
                    \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL
                );
            }
        } catch (\Exception $e) {
            $adminUrl = null;
        }

        // Fallback: build from admin directory
        if (!$adminUrl) {
            $adminDir = basename(_PS_ADMIN_DIR_);
            $domain = \Tools::getShopDomainSsl(true);
            $adminUrl = $domain . '/' . $adminDir . '/index.php/improve/modules/manage/action/configure/payplug';
        }

        // Only write to DB if value has changed
        $current = \Configuration::get('PAYPLUG_ADMIN_RETURN_URL');
        if ($current !== $adminUrl) {
            \Configuration::updateValue('PAYPLUG_ADMIN_RETURN_URL', $adminUrl);
        }
    }

    /**
     * Retrieve the stored admin return URL (single-use).
     *
     * @return string|false
     */
    public static function getAdminReturnUrl()
    {
        $url = \Configuration::get('PAYPLUG_ADMIN_RETURN_URL');

        if (!$url) {
            return false;
        }

        // Single-use: clear after retrieval
        \Configuration::deleteByName('PAYPLUG_ADMIN_RETURN_URL');

        // Validate the URL domain matches this shop
        $shopDomain = \Tools::getShopDomainSsl(false);
        $urlHost = parse_url($url, PHP_URL_HOST);
        if ($urlHost !== $shopDomain) {
            return false;
        }

        return $url;
    }
}
