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

namespace PayPlug\src\utilities\helpers;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\classes\DependenciesClass;

class LanguageHelper
{
    const ONEY_WIDGET_LANGUAGES = ['FR-FR', 'PT-PT', 'ES-ES', 'IT-IT', 'RO-RO', 'FR-BE', 'NL-BE', 'EN-NL', 'NL-NL', 'EN-PT', 'EN-EU'];
    const ONEY_WIDGET_DEFAULT_LANGUAGE = 'EN-EU';

    private $dependencies;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
    }

    /**
     * @description Get code iso from a given language code lang
     */
    public function getIsoFromCodeLang(string $language_code): string
    {
        $parse = explode('-', $language_code);

        return $this->dependencies
            ->getPlugin()
            ->getTools()
            ->tool('strtolower', $parse[0]);
    }

    /**
     * @description Get the language accepted by the Oney widget for a given language code
     */
    public function getOneyWidgetLanguage(?string $language_code): string
    {
        $normalized = strtoupper(trim((string) $language_code));

        return in_array($normalized, self::ONEY_WIDGET_LANGUAGES, true)
            ? $normalized
            : self::ONEY_WIDGET_DEFAULT_LANGUAGE;
    }
}
