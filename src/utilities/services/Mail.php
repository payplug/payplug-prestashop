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

namespace PayPlug\src\utilities\services;

use PayPlug\classes\DependenciesClass;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Mail
{
    public $dependencies;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
    }

    /**
     * @description Send a mail to PayPlug merchant and Payplug CS
     */
    public function sendMail()
    {
        $context = $this->dependencies->getPlugin()->getContext()->get();
        $email_vars = [
            '{shop_name}' => $this->dependencies
                ->getPlugin()
                ->getConfiguration()
                ->get('PS_SHOP_NAME'),
        ];

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getMailTranslations();

        $mail = $this->dependencies->getPlugin()->getMail();
        // Send mail to merchant
        $mail->send(
            $context->language->id,
            'disconnected_merchant',
            $translation['merchant']['subject'],
            $email_vars,
            (string) $this->dependencies
                ->getPlugin()
                ->getConfiguration()
                ->get('PS_SHOP_EMAIL'),
            (string) $this->dependencies
                ->getPlugin()
                ->getConfiguration()
                ->get('PS_SHOP_NAME'),
            (string) $this->dependencies
                ->getPlugin()
                ->getConfiguration()
                ->get('PS_SHOP_EMAIL'),
            (string) $this->dependencies
                ->getPlugin()
                ->getConfiguration()
                ->get('PS_SHOP_NAME'),
            null,
            null,
            _PS_MODULE_DIR_ . 'payplug/mails/'
        );

        // Send mail to CS
        $mail->send(
            $context->language->id,
            'disconnected_cs',
            $translation['cs']['subject'] . ' - ' . $this->dependencies
                ->getPlugin()
                ->getConfiguration()
                ->get('PS_SHOP_NAME'),
            $email_vars,
            'supportplugins@payplug.com',
            (string) $this->dependencies
                ->getPlugin()
                ->getConfiguration()
                ->get('PS_SHOP_NAME'),
            'supportplugins@payplug.com',
            (string) $this->dependencies
                ->getPlugin()
                ->getConfiguration()
                ->get('PS_SHOP_NAME'),
            null,
            null,
            _PS_MODULE_DIR_ . 'payplug/mails/'
        );
    }
}
