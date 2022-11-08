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

namespace PayPlug\src\repositories;

use Exception;
use PayPlug\src\application\dependencies\BaseClass;
use PayPlug\src\models\entities\CardEntity;

class CardRepository extends BaseClass
{
    protected $dependencies;

    private $cardEntity;
    private $configurationAdapter;
    private $constant;
    private $query;
    private $logger;
    private $toolsAdapter;

    public function __construct(
        $configurationAdapter,
        $constant,
        $dependencies,
        $logger,
        $query,
        $tools
    ) {
        $this->cardEntity = new CardEntity();
        $this->configurationAdapter = $configurationAdapter;
        $this->constant = $constant;
        $this->dependencies = $dependencies;
        $this->logger = $logger;
        $this->query = $query;
        $this->toolsAdapter = $tools;

        $isSandbox = $this->configurationAdapter->get(
            $this->dependencies->getConfigurationKey('sandboxMode')
        );
        $idCompany = $this->configurationAdapter->get(
            $this->dependencies->getConfigurationKey('companyId') . ($isSandbox ? '_TEST' : '')
        );
        $this->logger->setParams(['process' => 'cardRepository']);

        $this->cardEntity
            ->setAllowedBrand(['mastercard', 'visa'])
            ->setFieldsRequired([])
            ->setFieldsSize([])
            ->setFieldsValidate([])
            ->setTable($this->dependencies->name . '_card')
            ->setIdentifier('')
        ;
        if ($idCompany && (!empty($idCompany))) {
            $this->cardEntity->setIdCompany((int) $idCompany);
        }

        if ($isSandbox) {
            $this->cardEntity->setIsSandbox((bool) $isSandbox);
        }
    }

    /**
     * @description Check if a card exists with given params
     *
     * @param string $paymentId
     * @param int    $companyId
     * @param bool   $isSandbox
     *
     * @return bool
     */
    public function checkExists($paymentId = false, $companyId = false, $isSandbox = false)
    {
        if (!$paymentId || !is_string($paymentId)) {
            $this->logger->addLog('Parameter $paymentId is null or invalid [checkExists]', 'error');
            $this->logger->addLog('$paymentId: ' . json_encode($paymentId), 'debug');

            return false;
        }

        if (!$companyId || !is_int($companyId)) {
            $this->logger->addLog('Parameter $companyId is null or invalid [checkExists]', 'error');
            $this->logger->addLog('$companyId: ' . json_encode($companyId), 'debug');

            return false;
        }

        $this->query
            ->select()
            ->fields('id_card')
            ->from($this->constant->get('_DB_PREFIX_') . $this->cardEntity->getTable())
            ->where('id_card = "' . $this->query->escape($paymentId) . '"')
            ->where('id_company = ' . (int) $companyId)
            ->where('is_sandbox = ' . (int) $isSandbox)
        ;

        try {
            $card = $this->query->build();
            if (!empty($card)) {
                return true;
            }
        } catch (Exception $e) {
            $this->logger->addLog('Error : Unable to get the card [checkExists]. Exception : ' . $e->getMessage());

            return false;
        }

        return false;
    }

    /**
     * @description confirm delete card message, called in front/cards.php
     *
     * @return mixed
     */
    public function confirmDeleteCardMessage()
    {
        return $this->dependencies->l('card.CardRepository.confirmDeleteCardMessage', 'cardrepository');
    }

    /**
     * @description Delete card
     *
     * @param int $id_customer
     * @param int $id_payplug_card
     *
     * @return bool
     */
    public function deleteCard($id_customer, $id_payplug_card)
    {
        if (!isset($id_customer)
            || !is_int($id_customer)
            || !isset($id_payplug_card)
            || !is_int($id_payplug_card)
        ) {
            $this->logger->addLog(
                'Error:  Bad parameters were passed to [deleteCard] '
                . '$id_customer: ' . json_encode($id_customer)
                . '$id_payplug_card ' . json_encode($id_payplug_card)
            );

            return false;
        }

        $card = $this->getCard($id_payplug_card);

        if (empty($card)) {
            $this->logger->addLog(
                'Error:  No Card found on [deleteCard] with payplug card id given: ' .
                '$id_payplug_card ' . (isset($id_payplug_card) ? $id_payplug_card : '')
            );

            return false;
        }

        if ($this->isValidExpiration((int) $card['exp_month'], (int) $card['exp_year'])) {
            if (!$this->deleteCardFromAPI($card['id_card'])) {
                return false;
            }
        }

        $this->query
            ->delete()
            ->from($this->constant->get('_DB_PREFIX_') . $this->cardEntity->getTable())
            ->where('id_payplug_card = ' . (int) $id_payplug_card)
            ->where('id_customer = ' . (int) $id_customer)
        ;

        try {
            if (!$this->query->build()) {
                $this->logger->addLog('Error occured while deleting the card'
                    . $id_payplug_card . 'from the DataBase', 'error');

                return false;
            }
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * @description Delete Card From API with a given ID Card
     *
     * @param string $id_card
     *
     * @return bool
     */
    public function deleteCardFromAPI($id_card = '')
    {
        if (!$id_card || !is_string($id_card)) {
            $this->logger->addLog('Error:  Bad parameters were passed to [deleteCardFromAPI]'
                . '$id_card: ' . json_encode($id_card));

            return false;
        }

        $delete = $this->dependencies->apiClass->deleteCard($id_card);

        if (200 == $delete['code']) {
            $json_answer = $delete['resource']['httpResponse'];

            if (isset($json_answer['object']) && $json_answer['object'] == 'error') {
                $message_log = 'Error occured while deleting the card' . $id_card . 'from the API [deleteCardFromAPI]';
                $this->logger->addLog($message_log, 'error');
                $this->logger->addLog('JSON answer: ' . json_encode($json_answer));

                return false;
            }
        }

        return true;
    }

    /**
     * @description Return successflull deleted card, called in front/cards.php
     *
     * @return mixed
     */
    public function deleteCardMessage()
    {
        return $this->dependencies->l('card.CardRepository.deleteCardMessage', 'cardrepository');
    }

    /**
     * @description Delete all cards for a given customer
     *
     * @param int $id_customer
     *
     * @return bool
     */
    public function deleteCards($id_customer)
    {
        if (!is_int($id_customer) || !isset($id_customer)) {
            $this->logger->addLog('Error: parameter $id_customer is empty or not integer [deleteCards]');

            return false;
        }

        $cardsToDelete = $this->getByCustomer((int) $id_customer, false);
        if (isset($cardsToDelete) && !empty($cardsToDelete) && sizeof($cardsToDelete)) {
            foreach ($cardsToDelete as $card) {
                if (!$this->deleteCard((int) $id_customer, (int) $card['id_payplug_card'])) {
                    $this->logger->addLog('Error : card can not be deleted [deleteCards]', 'error');
                    $this->logger->addLog('$card : ' . json_encode($card), 'debug');
                    $this->logger->addLog('$card[id_payplug_card] : ' . $card['id_payplug_card'], 'debug');
                    $this->logger->addLog('$id_customer: ' . $id_customer, 'debug');

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * ## From classes/__PayPlugCard.php ##
     *
     * @description Get collection of cards for a given customer
     *
     * @param int  $id_customer
     * @param bool $active_only
     *
     * @return array
     */
    public function getByCustomer($id_customer, $active_only = false)
    {
        if (!isset($id_customer) || !is_int($id_customer) || !$id_customer) {
            $this->logger->addLog('Parameter $id_customer is null or invalid [getByCustomer]', 'error');
            $this->logger->addLog('$customer: ' . json_encode($id_customer), 'debug');

            return [];
        }

        $this->query
            ->select()
            ->fields('*')
            ->from($this->constant->get('_DB_PREFIX_') . $this->cardEntity->getTable())
            ->where('`id_customer` = ' . (int) $id_customer)
            ->where('`id_company` = ' . (int) $this->cardEntity->getIdCompany())
            ->where('`is_sandbox` = ' . (int) $this->cardEntity->getIsSandbox())
        ;

        $cards = $this->query->build();

        if (!$cards) {
            $this->logger->addLog('$cards is null [getByCustomer]');

            return [];
        }

        // unset secret datas
        foreach ($cards as $key => &$card) {
            if (!$this->isValidExpiration((int) $card['exp_month'], (int) $card['exp_year'])) {
                $card['expired'] = true;
                if ($active_only) {
                    unset($cards[$key]);

                    continue;
                }
            } else {
                $card['expired'] = false;
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
     * @description Get a card from DB for a given ID
     *
     * @param $id_payplug_card
     *
     * @return bool
     */
    public function getCard($id_payplug_card)
    {
        if (!isset($id_payplug_card)
            || !is_int($id_payplug_card)
        ) {
            $this->logger->addLog('Error:  Bad parameters were passed to [getCard]'
                . '$id_' . $this->dependencies->name . '_card: ' . json_encode($id_payplug_card));

            return false;
        }

        $this->query
            ->select()
            ->fields('*')
            ->from($this->constant->get('_DB_PREFIX_') . $this->cardEntity->getTable())
            ->where('`id_payplug_card` = ' . (int) $id_payplug_card)
        ;

        try {
            $card = $this->query->build();
        } catch (Exception $exception) {
            return false;
        }

        if (empty($card)) {
            $this->logger->addLog('Error : No card found for these parameters [getCard]. $id_customer: '
                . '$id_' . $this->dependencies->name . '_card: ' . json_encode($id_payplug_card));

            return false;
        }

        return reset($card);
    }

    /**
     * @description  Get Card detail form a Payment resource
     *
     * @param object $payment
     *
     * @return array
     */
    public function getCardDetailFromPayment($payment)
    {
        if (!is_object($payment) || !$payment) {
            $this->logger->addLog(
                '[getCardDetailFromPayment] Param $payment is invalid: ' . json_encode($payment),
                'error'
            );

            return [];
        }

        return [
            'last4' => (isset($payment->card->last4)
                && (!empty($payment->card->last4)))
                ? $payment->card->last4
                : null,
            'country' => (isset($payment->card->country)
                && (!empty($payment->card->country)))
                ? $payment->card->country
                : null,
            'exp_year' => (isset($payment->card->exp_year)
                && (!empty($payment->card->exp_year)))
                ? $payment->card->exp_year
                : null,
            'exp_month' => (isset($payment->card->exp_month)
                && (!empty($payment->card->exp_month)))
                ? $payment->card->exp_month
                : null,
            'brand' => (isset($payment->card->brand)
                && (!empty($payment->card->brand)))
                ? $payment->card->brand
                : null,
        ];
    }

    /**
     * ## From classes/__PayPlugCard.php ##
     *
     * @description Check if a card can be used
     *
     * @param int $month
     * @param int $year
     *
     * @return bool
     */
    public function isValidExpiration($month = false, $year = false)
    {
        if (!$month || !is_int($month) || !$year || !is_int($year)) {
            $this->logger->addLog('Month or/and year is invalid [isValidExpiration]');
            $this->logger->addLog('Month: ' . json_encode($month) . ' / Year: ' . json_encode($year), 'debug');

            return false;
        }

        if ($year < (int) date('Y') || ($year == (int) date('Y') && $month < (int) date('m'))) {
            $this->logger->addLog('Card is expired [isValidExpiration]', 'Error');

            return false;
        }

        return true;
    }

    /**
     * @description Save a user card from a given payment resource
     *
     * @param $payment
     *
     * @return bool
     */
    public function saveCard($payment)
    {
        if (!isset($payment) || !is_object($payment)) {
            $this->logger->addLog('Parameter $payment is null or invalid [saveCard]', 'error');
            $this->logger->addLog('$payment: ' . json_encode($payment), 'debug');

            return false;
        }

        $config = $this->configurationAdapter;

        $brand = $payment->card->brand;
        if ($this->toolsAdapter->tool('strtolower', $brand) != 'mastercard'
            && $this->toolsAdapter->tool('strtolower', $brand) != 'visa'
        ) {
            $brand = 'none';
        }

        $customer_id = isset($payment->metadata['ID Client']) ?
            (int) $payment->metadata['ID Client'] :
            (int) $payment->metadata['Client'];
        $is_sandbox = (int) $config->get(
            $this->dependencies->getConfigurationKey('sandboxMode')
        );
        $company_id = (int) $config->get(
            $this->dependencies->getConfigurationKey('companyId') . ($is_sandbox ? '_TEST' : '')
        );

        $exists = $this->checkExists((string) $payment->card->id, (int) $company_id, (bool) $is_sandbox);
        if ($exists) {
            $this->logger->addLog('Error: this card with id_card = '
                . $payment->card->id . 'already exists [saveCard]');

            return false;
        }

        // insert the new card in database
        $this->query
            ->insert()
            ->into($this->constant->get('_DB_PREFIX_') . $this->cardEntity->getTable())
            ->fields('id_customer')->values((int) $customer_id)
            ->fields('id_company')->values((int) $company_id)
            ->fields('is_sandbox')->values((int) $is_sandbox)
            ->fields('id_card')->values($this->query->escape($payment->card->id))
            ->fields('last4')->values($this->query->escape($payment->card->last4))
            ->fields('exp_month')->values($this->query->escape($payment->card->exp_month))
            ->fields('exp_year')->values($this->query->escape($payment->card->exp_year))
            ->fields('brand')->values($this->query->escape($brand))
            ->fields('country')->values($this->query->escape($payment->card->country))
            ->fields('metadata')->values($this->query->escape(serialize($payment->card->metadata)));

        try {
            if (!$this->query->build()) {
                $this->logger->addLog(
                    '[saveCard] The card with id_card: ' . $payment->card->id .
                    ' $customer_id: ' . $customer_id .
                    ' $company_id: ' . $company_id .
                    ' $payment->card->last4: ' . $payment->card->last4 .
                    ' $payment->card->exp_month: ' . $payment->card->exp_month .
                    ' $payment->card->exp_year: ' . $payment->card->exp_year .
                    ' can not be inserted in the database, but there is no throw'
                );
                $this->logger->addLog('$payment : ' . json_encode($payment), 'debug');

                return false;
            }
        } catch (Exception $e) {
            $this->logger->addLog('Error : Unable to insert the card [saveCard]. Exception : ' . $e->getMessage());

            return false;
        }

        return true;
    }
}
