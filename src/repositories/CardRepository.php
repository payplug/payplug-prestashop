<?php
/**
 * 2013 - 2021 PayPlug SAS
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
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\repositories;

use Exception;
use Payplug\Exception\ConfigurationNotSetException;
use Payplug\Exception\NotFoundException;
use PayPlug\src\entities\CardEntity;

class CardRepository extends Repository
{
    private $cardEntity;
    private $configurationSpecific;
    private $constant;
    private $query;
    private $logger;
    private $toolsSpecific;

    public function __construct(
        $configurationSpecific,
        $constant,
        $logger,
        $payplug,
        $query,
        $tools
    ) {
        $this->cardEntity = new CardEntity();
        $this->configurationSpecific = $configurationSpecific;
        $this->constant = $constant;
        $this->logger = $logger;
        $this->query = $query;
        $this->payplug = $payplug;
        $this->toolsSpecific = $tools;
        $this->setParams();
    }

    private function setParams()
    {
        $config = $this->configurationSpecific;
        $idCompany = $config->get('PAYPLUG_COMPANY_ID');
        $isSandbox = $config->get('PAYPLUG_SANDBOX_MODE');
        $this->logger->setParams(['process' => 'cardRepository']);

        $this->cardEntity
            ->setAllowedBrand(['mastercard', 'visa'])
            ->setFieldsRequired([])
            ->setFieldsSize([])
            ->setFieldsValidate([])
            ->setTable('payplug_card')
            ->setIdentifier('');
        if ($idCompany && (!empty($idCompany))) {
            $this->cardEntity->setIdCompany((int)$idCompany);
        }

        if ($isSandbox) {
            $this->cardEntity->setIsSandbox((bool)$isSandbox);
        }
    }

    /**
     * @description Delete all cards for a given customer
     *
     * @param int $id_customer
     * @return bool
     */
    public function deleteCards($id_customer)
    {
        if (!is_int($id_customer) || !isset($id_customer)) {
            $this->logger->addLog('Error: parameter $id_customer is empty or not integer [deleteCards]');
            return false;
        }

        $cardsToDelete = $this->getCards($id_customer, false);
        if (isset($cardsToDelete) && !empty($cardsToDelete) && sizeof($cardsToDelete)) {
            foreach ($cardsToDelete as $card) {
                if (!$this->deleteCard($id_customer, $card['id_payplug_card'])) {
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
     * @description  Get collection of cards. Param : ID Customer (int) or payment (object)
     *
     * @param int | object $payment_or_id_customer
     * @param bool $active_only
     * @return array | bool
     */
    public function getCards($payment_or_id_customer, $active_only = false)
    {
        if (!isset($payment_or_id_customer)) {
            $this->logger->addLog('[getCards] Param $payment_or_id_customer is null: ', 'error');
            return false;
        }

        if (is_int($payment_or_id_customer)) {
            // Case 1 : Client ID passed
            $id_customer = $payment_or_id_customer;
        } elseif (is_object($payment_or_id_customer)) {
            // Case 2 : Payment object passed
            $payment = $payment_or_id_customer;
            return [
                0 => [
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
                ]
            ];
        } else {
            $this->logger->addLog('Error: Bad parameter detected while retrieving cards [getCards]');
            $this->logger->addLog('$payment_or_id_customer: ' . $payment_or_id_customer
                . ' is not a  customer_id or a payment object passed as parameter');
            return false;
        }

        $config = $this->configurationSpecific;
        $is_sandbox = (int)$config->get('PAYPLUG_SANDBOX_MODE');

        $this->query
            ->select()
            ->fields('pc.id_customer')
            ->fields('pc.id_payplug_card')
            ->fields('pc.id_company')
            ->fields('pc.last4')
            ->fields('pc.exp_month')
            ->fields('pc.exp_year')
            ->fields('pc.brand')
            ->fields('pc.country')
            ->fields('pc.metadata')
            ->from($this->constant->get('_DB_PREFIX_') . 'payplug_card', 'pc')
            ->where('pc.id_customer = ' . (int)$id_customer)
            ->where('pc.id_company = ' . (int)$config->get('PAYPLUG_COMPANY_ID' . ($is_sandbox ? '_TEST' : '')))
            ->where('pc.is_sandbox = ' . (int)$is_sandbox);

        $res_payplug_card = $this->query->build();

        if (!$res_payplug_card) {
            return [];
        } else {
            foreach ($res_payplug_card as $key => &$value) {
                if (
                    (int)$value['exp_year'] < (int)date('Y')
                    || ((int)$value['exp_year'] == (int)date('Y')
                        && (int)$value['exp_month'] < (int)date('m'))
                ) {
                    $value['expired'] = true;
                    if ($active_only) {
                        unset($res_payplug_card[$key]);
                        continue;
                    }
                } else {
                    $value['expired'] = false;
                }
                $value['expiry_date'] = date(
                    'm / y',
                    mktime(0, 0, 0, (int)$value['exp_month'], 1, (int)$value['exp_year'])
                );
            }
            return $res_payplug_card;
        }
    }

    /**
     * @description Delete card
     *
     * @param int $id_customer
     * @param int $id_payplug_card
     * @return bool
     */
    public function deleteCard($id_customer, $id_payplug_card)
    {
        if (
            !isset($id_customer) ||
            !($id_customer) ||
            !isset($id_payplug_card) ||
            !($id_payplug_card)
        ) {
            $this->logger->addLog(
                'Error:  Bad parameters were passed to [deleteCard] '
                . '$id_customer: ' . isset($id_customer) ? $id_customer : (''
                . '$id_payplug_card ' . isset($id_payplug_card) ? $id_payplug_card : '')
            );
            return false;
        }
        $config = $this->configurationSpecific;
        $is_sandbox = (int)$config->get('PAYPLUG_SANDBOX_MODE');
        $id_company = (int)$config->get('PAYPLUG_COMPANY_ID' . ($is_sandbox ? '_TEST' : ''));
        $id_card = $this->getCardId($id_customer, $id_payplug_card, $id_company);
        //TODO : addLog avec $id_customer, $id_payplug_card, $id_company, $id_card if uninstall = true

        if (!$id_card) {
            /*
             * To prevent Exceptions from API if no card found.
             *
             * No log needed, because we are uninstalling
             * (return true, to continue uninstalling)
             */
            return true;
        }

        try {
            $response = \Payplug\Card::delete($id_card);
            $json_answer = $response['httpResponse'];
        } catch (ConfigurationNotSetException $exception) {
            /*
             * Disconnected merchant account (in config plugin page):
             * Exception -> Can't connect to API
             *
             * No log needed, because we are uninstalling
             * (return true, to continue uninstalling)
             */
            return true;
        } catch (NotFoundException $exception) {
            /*
             * Exception-> Card not found in API
             * Not "return false", but exception returned :-/
             *
             * No log needed, because we are uninstalling
             * (return true, to continue uninstalling)
             */
            return true;
        }

        if (isset($json_answer['object']) && $json_answer['object'] == 'error') {
            $this->logger->addLog('Error occured while deleting the card'
                . $id_card . 'from the API [deleteCard]', 'error');
            $this->logger->addLog('JSON answer: ' . json_encode($json_answer));
            return false;
        } else {
            $this->query
                ->delete()
                ->from($this->constant->get('_DB_PREFIX_') . $this->cardEntity->getTable())
                ->where(
                    $this->constant->get('_DB_PREFIX_')
                    . $this->cardEntity->getTable() . '.id_card = \'' . (string)$id_card . '\''
                )
                ->build();
        }

        return true;
    }

    /**
     * @description  get card id
     *
     * @param int $id_customer
     * @param int $id_payplug_card
     * @param int $id_company
     * @return string | bool
     */
    public function getCardId($id_customer, $id_payplug_card, $id_company)
    {
        if (
            !isset($id_customer) ||
            !($id_customer) ||
            !isset($id_payplug_card) ||
            !($id_payplug_card) ||
            !isset($id_company) ||
            !($id_company)
        ) {
            $this->logger->addLog('Error:  Bad parameters were passed to [getCardId]'
                . '$id_customer: ' . $id_customer
                . '$id_payplug_card: ' . $id_payplug_card
                . '$id_company: ' . $id_company);
            return false;
        }
        $config = $this->configurationSpecific;
        $is_sandbox = (int)$config->get('PAYPLUG_SANDBOX_MODE');

        $cards = $this->query
            ->select()
            ->fields('pc.id_card')
            ->from($this->constant->get('_DB_PREFIX_') . 'payplug_card', 'pc')
            ->where('pc.id_customer = ' . (int)$id_customer)
            ->where('pc.id_payplug_card = ' . (int)$id_payplug_card)
            ->where('pc.id_company = ' . (int)$id_company)
            ->where('pc.is_sandbox = ' . (int)$is_sandbox)
            ->build();

        if (empty($cards)) {
            $this->logger->addLog('Error : No card found for these parameters [getCardId]. $id_customer: '
                . $id_customer
                . '$id_payplug_card : ' . $id_payplug_card
                . '$id_company: ' . $id_company);
            return false;
        } else {
            return $cards[0]['id_card'];
        }
    }

    /**
     * @description get Card Brand
     * @param $payment
     * @return bool |string
     */
    public function getCardBrandByPayment($payment)
    {
        if (!isset($payment)) {
            $this->logger->addLog('Parameter $payment is null [getCardBrandByPayment]', 'error');
            return false;
        }

        if (!is_object($payment)) {
            try {
                $payment = \Payplug\Payment::retrieve($payment);
            } catch (Exception $exception) {
                $this->logger->addLog('Error occured while retrieving  card brand by payment [getCardBrandPayment] '
                    . 'API Exception: ' . $exception->getMessage(), 'error');
                return false;
            }
        }

        if ($payment->card->brand != '') {
            $brand = $payment->card->brand;
        } else {
            $brand = '';
        }
        return $brand;
    }

    /**
     * @description get Card Expiry date
     * @param $payment
     * @return bool |string
     */
    public function getCardExpiryDateByPayment($payment)
    {
        if (!isset($payment)) {
            $this->logger->addLog('Parameter $payment is null [getCardExpiryDateByPayment]', 'error');
            return false;
        }

        if (!is_object($payment)) {
            try {
                $payment = \Payplug\Payment::retrieve($payment);
            } catch (Exception $exception) {
                $this->logger->addLog('Error occured while trying to get card expiry date by payment.
                                        [getCardExpiryDateByPayment]'
                    . 'API Exception: ' . $exception->getMessage(), 'error');
                return false;
            }
        }

        if ($payment->card->exp_month === null) {
            $card_expiry_date = '';
        } else {
            $card_expiry_date = date(
                'm/y',
                strtotime('01.' . $payment->card->exp_month . '.' . $payment->card->exp_year)
            );
        }
        return $card_expiry_date;
    }

    /**
     * @description  get the Card Mask
     * @param $payment
     * @return bool|string
     */
    public function getCardMaskByPayment($payment)
    {
        if (!isset($payment)) {
            $this->logger->addLog('Parameter $payment is null [getCardMaskByPayment]', 'error');
            return false;
        }

        if (!is_object($payment)) {
            try {
                $payment = \Payplug\Payment::retrieve($payment);
            } catch (Exception $exception) {
                $this->logger->addLog('Error occured while trying to retrieve card mask by payment 
                                        [getCardMaskByPayment]. API Exception: '
                    . $exception->getMessage(), 'error');
                return false;
            }
        }

        if ($payment->card->last4 != '') {
            $card_mask = '**** **** **** ' . $payment->card->last4;
        } else {
            $card_mask = '';
        }
        return $card_mask;
    }

    /**
     * @description
     * Determine which environnement is used
     *
     * @param $payment
     * @return bool
     */
    public function saveCard($payment)
    {
        if (!isset($payment)) {
            $this->logger->addLog('Parameter $payment is null [saveCard]', 'error');
            return false;
        }

        $config = $this->configurationSpecific;

        $brand = $payment->card->brand;
        if (
            $this->toolsSpecific->tool('strtolower', $brand) != 'mastercard'
            && $this->toolsSpecific->tool('strtolower', $brand) != 'visa'
        ) {
            $brand = 'none';
        }

        $is_sandbox = 0;
        $customer_id = isset($payment->metadata['ID Client']) ?
            (int)$payment->metadata['ID Client'] :
            $payment->metadata['Client'];
        $is_sandbox = (int)$config->get('PAYPLUG_SANDBOX_MODE');

        $company_id = (int)$config->get('PAYPLUG_COMPANY_ID' . ($is_sandbox ? '_TEST' : ''));

        // if card exists then return false
        $dbCard =
            $this->query
                ->select()
                ->fields('id_card')
                ->from($this->constant->get('_DB_PREFIX_') . $this->cardEntity->getTable())
                ->where('id_card = "' . $payment->card->id . '"')
                ->where('id_company = ' . (int)$company_id)
                ->where('is_sandbox = ' . (int)$is_sandbox)
                ->build();

        if ((isset($dbCard) && (!empty($dbCard)))) {
            $this->logger->addLog('Error: this card with id_card = '
                . $payment->card->id . 'already exists [saveCard]');
            $this->logger->addLog('$payment : ' . json_encode($payment), 'debug');
            $this->logger->addLog('$dbCard : ' . json_encode($dbCard), 'debug');
            return false;
        }

        // insert the new card in database
        $this->query
            ->insert()
            ->into($this->constant->get('_DB_PREFIX_') . $this->cardEntity->getTable())
            ->fields('id_customer')->values((int)$customer_id)
            ->fields('id_company')->values((int)$company_id)
            ->fields('is_sandbox')->values((int)$is_sandbox)
            ->fields('id_card')->values((string)$payment->card->id)
            ->fields('last4')->values((string)$payment->card->last4)
            ->fields('exp_month')->values((string)$payment->card->exp_month)
            ->fields('exp_year')->values((string)$payment->card->exp_year)
            ->fields('brand')->values((string)$brand)
            ->fields('country')->values((string)$payment->card->country)
            ->fields('metadata')->values((string)serialize($payment->card->metadata));
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

    /**
     * @description  delete card from API and shop 's database
     * ## From classes/__PayPlugCard.php ##
     * @param $idPayplugCard
     * @return bool
     */
    public function delete($idPayplugCard)
    {
        if (!isset($idPayplugCard)) {
            $this->logger->addLog(
                'Can not process deleting card because of empty idPayplugCard parameter [delete]',
                'error'
            );
            return false;
        }

        try {
            $table = $this->cardEntity->getTable();
            $this->query
                ->select()
                ->fields('id_card')
                ->from($this->constant->get('_DB_PREFIX_') . $table)
                ->where($this->constant->get('_DB_PREFIX_') . $table . '.id_' . $table . ' = ' . $idPayplugCard);

            $idCard = $this->query->build()[0]['id_card'];

            // Delete from API
            try {
                \Payplug\Card::delete($idCard);
            } catch (Exception $e) {
                $this->logger->addLog('[delete] Error while deleting the card with $idcard. API exception: '
                    . $e->getMessage());
                $this->logger->addLog('$idCard : ' . $idCard, 'debug');
            }

            // Delete from our DB
            $this->query
                ->delete()
                ->from($this->constant->get('_DB_PREFIX_') . $table)
                ->where($this->constant->get('_DB_PREFIX_') . $table . '.id_card = \'' . (string)$idCard . '\'')
                ->build();

            return '<script type="text/javascript">document.location.reload(true);</script>';
        } catch (Exception $e) {
            if ($e->getCode() == '404') { // resource cant be found
                return parent::delete();
            }
            $this->logger->addLog('Can not delete card [delete]. ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * ## From classes/__PayPlugCard.php ##
     * @description Get database fields for compatibility 1.4
     * @return mixed
     */
    public function getFields()
    {
        $card = $this->cardEntity;
        $id = $this->cardEntity->getId();

        if (!isset($card) || !isset($id)) {
            $this->logger->addLog('$card or $id is null [getFields]');
            $this->logger->addLog('$this->cardEntity : ' . json_encode($this->cardEntity), 'debug');
            return false;
        }

        $fields = [];
        if (isset($id)) {
            $fields['id_payplug_card'] = (int)($id);
        }
        $fields['id_customer'] = is_null($card->getIdCustomer()) ? 0 : (int)($card->getIdCustomer());
        $fields['id_company'] = is_null($card->getIdCompany()) ? 0 : (int)($card->getIdCompany());
        $fields['is_sandbox'] = (int)$card->getIsSandbox();
        $fields['id_card'] = (string)$card->getIdCard();
        $fields['last4'] = (string)$card->getLast4();
        $fields['exp_month'] = (string)$card->getExpMonth();
        $fields['exp_year'] = (string)$card->getExpYear();
        $fields['brand'] = (string)$card->getBrand();
        $fields['country'] = (string)$card->getCountry();
        $fields['metadata'] = (string)$card->getMetadata();

        return $fields;
    }

    /**
     * ## From classes/__PayPlugCard.php ##
     * @description Get collection of cards for a given customer
     *
     * @param $customer
     * @param bool $active_only
     * @return array OR bool
     */
    public function getByCustomer($customer, $active_only = false)
    {
        if (!isset($customer)) {
            $this->logger->addLog('Parameter $customer is null [getByCustomer]');
        }

        $this->query
            ->select()
            ->fields('*')
            ->from($this->constant->get('_DB_PREFIX_') . $this->cardEntity->getTable())
            ->where('`id_customer` = ' . ((isset($customer->id) && !empty($customer->id)) ? $customer->id : $customer))
            ->where('`id_company` = ' . (int)$this->cardEntity->getIdCompany())
            ->where('`is_sandbox` = ' . (int)$this->cardEntity->getIsSandbox());

        $cards = $this->query->build();

        if (!$cards) {
            $this->logger->addLog('$cards is null [getByCustomer]');
        }

        // unset secret datas
        foreach ($cards as $key => &$card) {
            if (!$this::isValidExpiration((int)$card['exp_month'], (int)$card['exp_year'])) {
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
                mktime(0, 0, 0, (int)$card['exp_month'], 1, (int)$card['exp_year'])
            );

            unset($card['is_sandbox']);
            unset($card['id_card']);
        }
        return $cards;
    }

    /**
     * ## From classes/__PayPlugCard.php ##
     * @description Check if a card can be used
     *
     * @param int $month
     * @param int $year
     * @return bool
     */
    public function isValidExpiration($month, $year)
    {
        if (!isset($month) || !isset($year)) {
            $this->logger->addLog('Month or/and year is not specified [isValidExpiration]');
            $this->logger->addLog(
                'Month: ' . isset($month) ? $month : ('' .
                'Year: ' . isset($year) ? $year : ''),
                'debug'
            );
            return false;
        }

        if ($year < (int)date('Y') || ($year == (int)date('Y') && $month < (int)date('m'))) {
            $this->logger->addLog('Card is expired [isValidExpiration]', 'Error');
            return false;
        }

        return true;
    }

    /**
     * @description confirm delete card message, called in front/cards.php
     * @return mixed
     */
    public function confirmDeleteCardMessage()
    {
        return $this->l('card.CardRepository.confirmDeleteCardMessage');
    }
    /**
     * @description Return successflull deleted card, called in front/cards.php
     * @return mixed
     */
    public function deleteCardMessage()
    {
        return $this->l('card.CardRepository.deleteCardMessage');
    }
}
