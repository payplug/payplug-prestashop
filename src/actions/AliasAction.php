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

namespace PayPlug\src\actions;

use PayPlug\classes\DependenciesClass;
use PayPlug\src\utilities\traits\ServiceGetter;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AliasAction
{
    use ServiceGetter;

    private const REPOSITORY_SERVICE = 'payplug.models.repositories.alias';

    public $dependencies;

    /**
     * @description Build the module dependencies for this action
     */
    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
    }

    /**
     * @description Render the unexpired saved aliases of the current customer
     *
     * @return array
     */
    public function renderList()
    {
        $id_customer = $this->getCurrentCustomerId();
        if (!$id_customer) {
            return [];
        }

        $aliases = $this->getService(self::REPOSITORY_SERVICE)->getAllByCustomer($id_customer);
        if (empty($aliases)) {
            return [];
        }

        $prestashop_adapter = $this->dependencies->loadAdapterPresta();
        $list = [];
        foreach ($aliases as $alias) {
            if ($this->isExpired($alias)) {
                continue;
            }

            $identifier = $prestashop_adapter
                ? (string) $prestashop_adapter->getHostedFieldsIdentifier((string) $alias['currency'])
                : '';
            $list[] = $this->formatAlias($alias, '' !== $identifier && $identifier === (string) $alias['identifier']);
        }

        return $list;
    }

    /**
     * @description Render the saved aliases of the current customer usable for a currency and UHF identifier
     *
     * @param string $currency ISO code, any case
     * @param string $identifier
     *
     * @return array
     */
    public function renderCheckoutList($currency = '', $identifier = '')
    {
        if (!is_string($currency) || !$currency || !is_string($identifier) || !$identifier) {
            return [];
        }

        $id_customer = $this->getCurrentCustomerId();
        if (!$id_customer) {
            return [];
        }

        $aliases = $this->getService(self::REPOSITORY_SERVICE)->getAllByCustomer($id_customer);
        $currency = strtolower($currency);
        $list = [];
        foreach ($aliases as $alias) {
            if ($currency !== strtolower((string) $alias['currency'])
                || $identifier !== (string) $alias['identifier']
                || $this->isExpired($alias)) {
                continue;
            }

            $list[] = $this->formatAlias($alias, true);
        }

        return $list;
    }

    /**
     * @description Delete a saved alias of the given customer, locally only
     *
     * @param int $id_customer
     * @param int $id_payplug_alias
     *
     * @return bool
     */
    public function deleteAction($id_customer = 0, $id_payplug_alias = 0)
    {
        if (!is_int($id_customer) || $id_customer <= 0) {
            $this->log('AliasAction::deleteAction - Invalid argument, given customer id must be non null integer.');

            return false;
        }

        if (!is_int($id_payplug_alias) || $id_payplug_alias <= 0) {
            $this->log('AliasAction::deleteAction - Invalid argument, given alias id must be non null integer.');

            return false;
        }

        $alias_repository = $this->getService(self::REPOSITORY_SERVICE);
        $alias = $alias_repository->getEntity($id_payplug_alias);
        if (empty($alias)) {
            $this->log('AliasAction::deleteAction - Can\'t get the related alias.');

            return false;
        }

        if ((int) $alias['id_customer'] !== $id_customer) {
            $this->log('AliasAction::deleteAction - Given customer id does not match.');

            return false;
        }

        return (bool) $alias_repository->deleteEntity($id_payplug_alias);
    }

    /**
     * @description Delete every saved alias of a customer (GDPR)
     *
     * @param int $id_customer
     *
     * @return bool true when the customer has no alias left
     */
    public function deleteByCustomerAction($id_customer = 0)
    {
        if (!is_int($id_customer) || $id_customer <= 0) {
            $this->log('AliasAction::deleteByCustomerAction - Invalid argument, given customer id must be non null integer.');

            return false;
        }

        $alias_repository = $this->getService(self::REPOSITORY_SERVICE);
        if (empty($alias_repository->getAllByCustomer($id_customer))) {
            return true;
        }

        return (bool) $alias_repository->deleteBy('id_customer', $id_customer);
    }

    /**
     * @description Export the saved aliases of a customer (GDPR), in the gdprCardExport() row format
     *
     * Without the '#' column: the caller numbers the card and alias rows together.
     *
     * @param int $id_customer
     *
     * @return array
     */
    public function gdprExportAction($id_customer = 0)
    {
        if (!is_int($id_customer) || $id_customer <= 0) {
            return [];
        }

        $aliases = $this->getService(self::REPOSITORY_SERVICE)->getAllByCustomer($id_customer);
        if (empty($aliases)) {
            return [];
        }

        $translation = $this->dependencies->getPlugin()->getTranslationClass();
        $result = [];
        foreach ($aliases as $alias) {
            $formatted = $this->formatAlias($alias, false);
            $result[] = [
                $translation->l('payplug.gdprCardExport.brand', 'configclass') => $formatted['brand'],
                $translation->l('payplug.gdprCardExport.card', 'configclass') => '**** **** **** ' . $formatted['last4'],
                $translation->l('payplug.gdprCardExport.expiryDate', 'configclass') => $formatted['expiry_date'],
            ];
        }

        return $result;
    }

    /**
     * @description Tell whether an alias has a known expiry date in the past
     *
     * Shared with OperationAction::createAction(), which refuses to pay with an expired alias.
     *
     * @param array $alias AliasRepository row
     *
     * @return bool
     */
    public function isExpired(array $alias)
    {
        if (empty($alias['exp_month']) || empty($alias['exp_year'])) {
            return false;
        }

        $validity = $this->dependencies
            ->getValidators()['card']
            ->isValidExpiration((string) $alias['exp_month'], (string) $alias['exp_year']);

        return !$validity['result'];
    }

    /**
     * @description Get the id of the current registered, non-guest customer
     *
     * @return int 0 for a guest or an anonymous visitor
     */
    private function getCurrentCustomerId()
    {
        $context = $this->dependencies->getPlugin()->getContext()->get();
        $customer = is_object($context) && isset($context->customer) ? $context->customer : null;

        if (!is_object($customer) || !isset($customer->id) || (int) $customer->id <= 0 || !empty($customer->is_guest)) {
            return 0;
        }

        return (int) $customer->id;
    }

    /**
     * @description Format an alias row for the templates
     *
     * @param bool $usable
     *
     * @return array
     */
    private function formatAlias(array $alias, $usable)
    {
        // AliasRepository::saveIfAbsent() only stores complete aliases: every card detail is set.
        // The Unified API alias id is left out: it never leaves the server.
        return [
            'id_payplug_alias' => (int) $alias['id_payplug_alias'],
            'currency' => (string) $alias['currency'],
            'brand' => (string) $alias['brand'],
            'last4' => (string) $alias['last4'],
            'expiry_date' => date('m / y', mktime(0, 0, 0, (int) $alias['exp_month'], 1, (int) $alias['exp_year'])),
            'usable' => (bool) $usable,
        ];
    }

    /**
     * @description Log an error through the module logger
     *
     * @param string $message
     */
    private function log($message)
    {
        $this->dependencies->getPlugin()->getLogger()->addLog($message, 'error');
    }
}
