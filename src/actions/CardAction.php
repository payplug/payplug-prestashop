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

if (!defined('_PS_VERSION_')) {
    exit;
}

class CardAction
{
    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @description Delete card in database and api from given card and customer id.
     *
     * @param int $customer_id
     * @param int $card_id
     *
     * @return bool
     */
    public function deleteAction($customer_id = 0, $card_id = 0)
    {
        if (!is_int($customer_id) || !$customer_id) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('CardAction::deleteAction - Invalid argument, given customer id must be non null integer.', 'error');

            return false;
        }

        if (!is_int($card_id) || !$card_id) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('CardAction::deleteAction - Invalid argument, given card id must be non null integer.', 'error');

            return false;
        }

        $card = $this->dependencies
            ->getPlugin()
            ->getCardRepository()
            ->getEntity((int) $card_id);

        if (empty($card)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('CardAction::deleteAction - Can\'t get the related card', 'error');

            return false;
        }

        // Check correspondance between the retrieved customer id and given one
        if ($card['id_customer'] != $customer_id) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('CardAction::deleteAction - Given customer id does not match', 'error');

            return false;
        }

        // Check card expiration then delete it in API if valid
        $validate_expiration = $this->dependencies
            ->getValidators()['card']
            ->isValidExpiration((int) $card['exp_month'], (int) $card['exp_year']);

        if ($validate_expiration['result']) {
            $delete = $this->dependencies
                ->getPlugin()
                ->getApiService()
                ->deleteCard($card['id_card']);
            if ($delete['result']) {
                $json_answer = $delete['resource']['httpResponse'];
                if (isset($json_answer['object']) && 'error' == $json_answer['object']) {
                    return false;
                }
            }
        }

        return (bool) $this->dependencies
            ->getPlugin()
            ->getCardRepository()
            ->deleteEntity((int) $card_id);
    }

    /**
     * @description Delete card in database and api from given customer id.
     *
     * @param int $customer_id
     *
     * @return bool
     */
    public function deleteByCustomerAction($customer_id = 0)
    {
        if (!is_int($customer_id) || !$customer_id) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('CardAction::deleteByCustomerAction - Invalid argument, given customer id must be non null integer.', 'error');

            return false;
        }

        $configuration = $this->dependencies
            ->getPlugin()
            ->getConfigurationClass();
        $is_sandbox = $configuration->getValue('sandbox_mode');
        $id_company = $configuration->getValue('company_id');

        $cards = $this->dependencies
            ->getPlugin()
            ->getCardRepository()
            ->getAllByCustomer((int) $customer_id, (int) $id_company, (bool) $is_sandbox);

        if (empty($cards)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('CardAction::deleteByCustomerAction - No card found for given customer.', 'error');

            return false;
        }

        foreach ($cards as $card) {
            $deleted = $this->deleteAction((int) $customer_id, (int) $card['id_payplug_card']);
            if (!$deleted) {
                $this->dependencies
                    ->getPlugin()
                    ->getLogger()
                    ->addLog('CardAction::deleteByCustomerAction - Can\'t delete card.', 'error');

                return false;
            }
        }

        return true;
    }

    /**
     * @description Render the cards list.
     *
     * @param bool $active_only
     *
     * @return array
     */
    public function renderList($active_only = false)
    {
        if (!is_bool($active_only)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('CardAction::renderList - Invalid argument, given parameter must be boolean', 'error');

            return [];
        }

        $customer = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get()
            ->customer;

        $configuration = $this->dependencies
            ->getPlugin()
            ->getConfigurationClass();
        $is_sandbox = $configuration->getValue('sandbox_mode');
        $id_company = $configuration->getValue('company_id');

        $cards = $this->dependencies
            ->getPlugin()
            ->getCardRepository()
            ->getAllByCustomer((int) $customer->id, (int) $id_company, (bool) $is_sandbox);
        if (empty($cards)) {
            return [];
        }

        foreach ($cards as $key => &$card) {
            $is_expired = $this->dependencies
                ->getValidators()['card']
                ->isValidExpiration((string) $card['exp_month'], (string) $card['exp_year']);
            if (!$is_expired['result']) {
                unset($cards[$key]);

                continue;
            }
            $card['expiry_date'] = date(
                'm / y',
                mktime(0, 0, 0, (int) $card['exp_month'], 1, (int) $card['exp_year'])
            );
            unset($card['is_sandbox'], $card['id_card']);
        }

        return $cards;
    }

    /**
     * @description Render card information for order detail.
     *
     * @param object $payment
     *
     * @return array
     */
    public function renderOrderDetail($payment = null)
    {
        if (!is_object($payment) || !$payment) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('CardAction::renderOrderDetail - Invalid argument, given resource must be object', 'error');

            return [];
        }

        return [
            'last4' => isset($payment->card->last4) && !empty($payment->card->last4) ? $payment->card->last4 : null,
            'country' => isset($payment->card->country) && !empty($payment->card->country) ? $payment->card->country : null,
            'exp_year' => isset($payment->card->exp_year) && !empty($payment->card->exp_year) ? $payment->card->exp_year : null,
            'exp_month' => isset($payment->card->exp_month) && !empty($payment->card->exp_month) ? $payment->card->exp_month : null,
            'brand' => isset($payment->card->brand) && !empty($payment->card->brand) ? $payment->card->brand : null,
        ];
    }

    /**
     * @description Save card in database from a given payment.
     *
     * @param object $payment
     *
     * @return bool
     */
    public function saveAction($payment = null)
    {
        if (!is_object($payment) || !$payment) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('CardAction::saveAction - Invalid argument, given resource must be object', 'error');

            return false;
        }

        $is_sandbox = !(bool) $payment->is_live;
        $customer_id = $payment->metadata['ID Client'];
        $company_id = (int) $this->dependencies
            ->getPlugin()
            ->getConfigurationClass()
            ->getValue('company_id');
        $exists = $this->dependencies
            ->getPlugin()
            ->getCardRepository()
            ->exists((string) $payment->card->id, (int) $company_id, $is_sandbox);

        if ($exists) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('CardAction::saveAction - No card found for given payment resource.', 'error');

            return false;
        }

        $parameters = [
            'id_customer' => (int) $customer_id,
            'id_company' => (int) $company_id,
            'is_sandbox' => (bool) $is_sandbox,
            'id_card' => (string) $payment->card->id,
            'last4' => (string) $payment->card->last4,
            'exp_month' => (string) $payment->card->exp_month,
            'exp_year' => (string) $payment->card->exp_year,
            'brand' => (string) $payment->card->brand,
            'country' => (string) $payment->card->country,
            'metadata' => json_encode($payment->card->metadata),
        ];

        return (bool) $this->dependencies
            ->getPlugin()
            ->getCardRepository()
            ->createEntity($parameters);
    }

    /**
     * @description Delete all cards register in database.
     *
     * @return bool
     */
    public function uninstallAction()
    {
        $cards = $this->dependencies
            ->getPlugin()
            ->getCardRepository()
            ->getAll();

        if (empty($cards)) {
            return true;
        }

        foreach ($cards as $card) {
            $id_customer = $card['id_customer'];
            $id_payplug_card = $card['id_payplug_card'];
            $deleted = $this->deleteAction((int) $id_customer, (int) $id_payplug_card);
            if (!$deleted) {
                return false;
            }
        }

        return true;
    }
}
