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

namespace PayPlug\src\models\classes;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\src\utilities\traits\ServiceGetter;
use PayplugUnifiedCore\Contracts\IConfigurationRepository;

class UpcConfigurationRepository implements IConfigurationRepository
{
    use ServiceGetter;

    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    public function get(string $key): ?string
    {
        $value = $this->dependencies->getPlugin()->getConfiguration()->get($this->prefixedKey($key));

        return ('' === $value || false === $value || null === $value) ? null : (string) $value;
    }

    public function set(string $key, string $value): void
    {
        $this->dependencies->getPlugin()->getConfiguration()->updateValue($this->prefixedKey($key), $value);
    }

    /**
     * Delegates to Merchant::getOauthClientField() — the OAuth2 client-credentials pair
     * already obtained at merchant onboarding is merchant state, not something this contract
     * adapter should own itself.
     */
    public function getClientId(): string
    {
        return $this->getService('payplug.models.classes.merchant')->getOauthClientField('client_id');
    }

    public function getClientSecret(): string
    {
        return $this->getService('payplug.models.classes.merchant')->getOauthClientField('client_secret');
    }

    /**
     * Obsolete: belonged to the pre-Unified Hosted Fields integration. The Unified Hosted Fields
     * form is now initialized front-side from the company_ref returned by API::getAccount()
     * instead — unrelated to this contract. No caller in this ticket's flow uses this method; logged
     * so a future UPC library version calling it doesn't fail silently.
     */
    public function getPublicKeyId(): string
    {
        $this->dependencies->getPlugin()->getLogger()->addLog(
            'UpcConfigurationRepository::getPublicKeyId - obsolete method called, returning empty string',
            'info'
        );

        return '';
    }

    public function getPublicKeyValue(): string
    {
        $this->dependencies->getPlugin()->getLogger()->addLog(
            'UpcConfigurationRepository::getPublicKeyValue - obsolete method called, returning empty string',
            'info'
        );

        return '';
    }

    private function prefixedKey(string $key): string
    {
        return 'PAYPLUG_UPC_' . strtoupper($key);
    }
}
