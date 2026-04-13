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
 *  DISCLAIMER
 *
 *  Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 *  versions in the future.
 *
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\src\utilities\helpers\LinkHelper;

/**
 * Front-office controller for OAuth2 callback from PayPlug.
 */
class PayplugOauthcallbackModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        // Security headers
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Referrer-Policy: no-referrer');

        // Request-binding check: reject any callback that does not carry the
        // state nonce generated when the flow was started from the back-office.
        // This prevents unauthenticated actors from triggering OAuth mutations.
        $state = Tools::getValue('authState');
        $storedState = LinkHelper::getStoredOAuthState();

        if (!$state || !$storedState || !hash_equals($storedState, $state)) {
            header('HTTP/1.1 403 Forbidden');

            exit('Forbidden');
        }

        // Handle OAuth error responses (e.g. user cancelled authorization)
        $oauthError = Tools::getValue('error');
        if ($oauthError) {
            LinkHelper::clearOAuthState();
            $returnUrl = LinkHelper::getAdminReturnUrl();
            Tools::redirect($returnUrl ?: $this->context->link->getPageLink('index'));

            return;
        }

        $dependencies = new PayPlug\classes\DependenciesClass();
        $configAction = $dependencies->getPlugin()->getConfigurationAction();

        // Registration flow: PayPlug redirects with client_id + company_id
        $clientId = Tools::getValue('client_id');
        $companyId = Tools::getValue('company_id');
        if ($clientId && $companyId) {
            if (!preg_match('/^[a-f0-9\-]{36}$/i', $clientId) || !preg_match('/^[a-f0-9\-]{36}$/i', $companyId)) {
                header('HTTP/1.1 400 Bad Request');

                exit('Bad Request');
            }
            $configAction->registerOauthRequestAction($clientId, $companyId);
            // registerOauthRequestAction calls initiateOAuth which sends a Location header
            // to PayPlug's authorization page. Exit so that header takes effect.
            // State is intentionally kept alive for the upcoming auth-code callback.

            exit;
        }

        // Authorization code flow: PayPlug redirects with code + state
        $code = Tools::getValue('code');
        if ($code) {
            $result = $configAction->oauthLoginAction($code);
            // Flow complete: invalidate the state nonce so it cannot be replayed.
            LinkHelper::clearOAuthState();
            if (empty($result['result'])) {
                PrestaShopLogger::addLog(
                    'PayPlug OAuth callback: login failed — ' . ($result['message'] ?? 'unknown error'),
                    3
                );
            }
        }

        // Redirect back to admin config page
        $returnUrl = LinkHelper::getAdminReturnUrl();
        if ($returnUrl) {
            Tools::redirect($returnUrl);
        }

        // Fallback: redirect to shop homepage
        Tools::redirect($this->context->link->getPageLink('index'));
    }
}
