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
use PayPlug\src\specific\ToolsSpecific;

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
        $query
    ) {

        $this->cardEntity = new CardEntity();
        $this->configurationSpecific = $configurationSpecific;
        $this->constant = $constant;
        $this->logger = $logger;
        $this->query = $query;
        $this->payplug = $payplug;
        $this->toolsSpecific = new ToolsSpecific();
        $this->logger->setParams(['process' => 'cardRepository']);
        $this->setParams();
    }

    private function setParams()
    {
        $config = $this->configurationSpecific;
        $idCompany = $config->get('PAYPLUG_COMPANY_ID');
        $isSandbox = $config->get('PAYPLUG_SANDBOX_MODE');

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

    public static function factory()
    {
        return new CardRepository();
    }

    /**
     * Delete all cards for a given customer
     *
     * @param int $id_customer
     * @return bool
     * @throws ConfigurationNotSetException
     */
    public function deleteCards($id_customer)
    {
        $cardsToDelete = $this->getCards($id_customer, false);
        if (isset($cardsToDelete) && !empty($cardsToDelete) && sizeof($cardsToDelete)) {
            foreach ($cardsToDelete as $card) {
                if (!$this->deleteCard($id_customer, $card['id_payplug_card'])) {
                    $this->logger->addLog('Error : card can not be deleted');
                    $this->logger->addLog('id_payplug_card : ' . $card['id_payplug_card']);

                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get collection of cards. Param : ID Customer (int) or payment (object)
     *
     * @param int | object $payment_or_id_customer
     * @param bool $active_only
     * @return array OR bool
     */
    public function getCards($payment_or_id_customer, $active_only = false)
    {
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
            // Add log
            $this->logger->addLog('Error: Bad parameter while retrieving cards');
            $this->logger->addLog($payment_or_id_customer
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
                if ((int)$value['exp_year'] < (int)date('Y')
                    || ((int)$value['exp_year'] == (int)date('Y')
                        && (int)$value['exp_month'] < (int)date('m'))) {
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
     * Delete card
     *
     * @param int $id_customer
     * @param int $id_payplug_card
     * @return bool
     * @throws ConfigurationNotSetException
     */
    public function deleteCard($id_customer, $id_payplug_card)
    {
        $config = $this->configurationSpecific;
        $is_sandbox = (int)$config->get('PAYPLUG_SANDBOX_MODE');
        $id_company = (int)$config->get('PAYPLUG_COMPANY_ID' . ($is_sandbox ? '_TEST' : ''));
        $id_card = $this->getCardId($id_customer, $id_payplug_card, $id_company);

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
            $this->logger->addLog('Error occured while deleting the card' . $id_card . 'from the API', 'error');
            return false;
        } else {
            $this->query
                ->delete()
                ->from($this->constant->get('_DB_PREFIX_') . $this->cardEntity->getTable())
                ->where(
                    $this->constant->get('_DB_PREFIX_')
                        . $this->cardEntity->getTable() . '.id_card = \'' . pSQL($id_card) . '\''
                )
                ->build();
        }

        return true;
    }

    /**
     * get card id
     *
     * @param int $id_customer
     * @param int $id_payplug_card
     * @param int $id_company
     * @return string
     */
    public function getCardId($id_customer, $id_payplug_card, $id_company)
    {
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
            $this->logger->addLog('Error : No card found for this id_customer '
                                  . $id_customer . 'with id_payplug_card  = ' . $id_payplug_card);
            return false;
        } else {
            return $cards[0]['id_card'];
        }
    }

    /**
     * @param $payment
     * @return Exception|string
     * @throws ConfigurationNotSetException
     */
    public function getCardBrandByPayment($payment)
    {
        if (!is_object($payment)) {
            try {
                $payment = \Payplug\Payment::retrieve($payment);
            } catch (Exception $exception) {
                $this->logger->addLog('Error while occured while retrieving  card brand bypayment '
                                      . $exception->getMessage(), 'error');
                return $exception;
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
     * @param $payment
     * @return Exception|false|string
     * @throws ConfigurationNotSetException
     */
    public function getCardExpiryDateByPayment($payment)
    {
        if (!is_object($payment)) {
            try {
                $payment = \Payplug\Payment::retrieve($payment);
            } catch (Exception $exception) {
                $this->logger->addLog('Error occured while trying to get card expiry date by payment '
                                     . $exception->getMessage(), 'error');
                return $exception;
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
     * @param $payment
     * @return Exception|string
     * @throws ConfigurationNotSetException
     */
    public function getCardMaskByPayment($payment)
    {
        if (!is_object($payment)) {
            try {
                $payment = \Payplug\Payment::retrieve($payment);
            } catch (Exception $exception) {
                $this->logger->addLog('Error occured while trying to retrieve card mask by payment'
                                      . $exception->getMessage(), 'error');
                return $exception;
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
     * Determine witch environnement is used
     *
     * @param PayplugPayment $payment
     * @return bool
     */
    public function saveCard($payment)
    {
        $config = $this->configurationSpecific;

        $brand = $payment->card->brand;
        if ($this->toolsSpecific->tool('strtolower', $brand) != 'mastercard'
            && $this->toolsSpecific->tool('strtolower', $brand) != 'visa') {
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
                                        . $payment->card->id . 'already exists');
            return false;
        }

        // insert the new card in database
        $this->query
            ->insert()
            ->into($this->constant->get('_DB_PREFIX_') . $this->cardEntity->getTable())
            ->fields('id_customer')->values((int)$customer_id)
            ->fields('id_company')->values((int)$company_id)
            ->fields('is_sandbox')->values((int)$is_sandbox)
            ->fields('id_card')->values(pSQL($payment->card->id))
            ->fields('last4')->values(pSQL($payment->card->last4))
            ->fields('exp_month')->values(pSQL($payment->card->exp_month))
            ->fields('exp_year')->values(pSQL($payment->card->exp_year))
            ->fields('brand')->values(pSQL($brand))
            ->fields('country')->values(pSQL($payment->card->country))
            ->fields('metadata')->values(pSQL(serialize($payment->card->metadata)));
        try {
            if (!$this->query->build()) {
                $this->logger->addLog(
                    'The card with id_card =' . $payment->card->id
                    . 'can not be inserted in the dabase, but there is no throw'
                );
                return false;
            }
        } catch (Exception $e) {
            $this->logger->addLog('Error : Unable to insert the card' . $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * ## From classes/__PayPlugCard.php ##
     * @param $idPayplugCard
     * @return bool
     * @throws ConfigurationNotSetException
     */
    public function delete($idPayplugCard)
    {
        if (!$idPayplugCard) {
            $this->logger->addLog(' Can not process deleting card because of empty idPayplugCard', 'error');
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
            \Payplug\Card::delete($idCard);

            // Delete from our DB
            $this->query
                ->delete()
                ->from($this->constant->get('_DB_PREFIX_') . $table)
                ->where($this->constant->get('_DB_PREFIX_') . $table . '.id_card = \'' . pSQL($idCard) . '\'')
                ->build();

//            $this->tools->tool('redirect',$_SERVER['HTTP_REFERER']); exit;
            return '<script type="text/javascript">document.location.reload(true);</script>';
        } catch (Exception $e) {
            if ($e->getCode() == '404') { // resource cant be found
                return parent::delete();
            }
            $this->logger->addLog('Can not delete card ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * ## From classes/__PayPlugCard.php ##
     * Get database fields for compatibility 1.4
     * @return mixed
     */
    public function getFields()
    {
        $card = $this->cardEntity;
        $id = $this->cardEntity->getId();
        $fields = [];
        if (isset($id)) {
            $fields['id_payplug_card'] = (int)($id);
        }
        $fields['id_customer'] = is_null($card->getIdCustomer()) ? 0 : (int)($card->getIdCustomer());
        $fields['id_company'] = is_null($card->getIdCompany()) ? 0 : (int)($card->getIdCompany());
        $fields['is_sandbox'] = (int)$card->isIsSandbox();
        $fields['id_card'] = pSQL($card->getIdCard());
        $fields['last4'] = pSQL($card->getLast4());
        $fields['exp_month'] = pSQL($card->getExpMonth());
        $fields['exp_year'] = pSQL($card->getExpYear());
        $fields['brand'] = pSQL($card->getBrand());
        $fields['country'] = pSQL($card->getCountry());
        $fields['metadata'] = pSQL($card->getMetadata());

        return $fields;
    }

    /**
     * ## From classes/__PayPlugCard.php ##
     * Get collection of cards fort a given customer
     *
     * @param Customer $customer
     * @param bool $active_only
     * @return array OR bool
     */
    public function getByCustomer($customer, $active_only = false)
    {
//        if (!is_object($customer)) {
//            $customer = new \Payplug\Customer((int)$customer);
//        }

        $this->query
            ->select()
            ->fields('*')
            ->from($this->constant->get('_DB_PREFIX_') . $this->cardEntity->getTable())
            ->where('`id_customer` = ' . ((isset($customer->id) && !empty($customer->id)) ? $customer->id : $customer))
            ->where('`id_company` = ' . (int)$this->cardEntity->getIdCompany())
            ->where('`is_sandbox` = ' . (int)$this->cardEntity->getIsSandbox());

        $cards = $this->query->build();

        // unset secret datas
        foreach ($cards as $key => &$card) {
            if (!$this::isValidExpiration((int)$card['exp_month'], (int)$card['exp_year'], $this->logger)) {
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
     * Check if a card can be used
     *
     * @param int $month
     * @param int $year
     * @return array OR bool
     */
    public static function isValidExpiration($month, $year, $logger)
    {
        if ($month == null || $year == null) {
            $logger->addLog('Month or/and date is not specified', 'error');
            return false;
        }

        if ($year < (int)date('Y') || ($year == (int)date('Y') && $month < (int)date('m'))) {
            $logger->addLog('Card is expired', 'Error');
            return false;
        }

        return true;
    }

    /**
     * @description Return successflull deleted card, called in front/cards.php
     * @return mixed
     */
    public function deleteCardMessage()
    {
        return $this->l('Card sucessfully deleted.');
    }
}
