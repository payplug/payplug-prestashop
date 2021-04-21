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

use Payplug\Exception\ConfigurationNotSetException;
use PayPlug\src\entities\CardEntity;
use PayPlug\src\specific\ConfigurationSpecific;
use PayPlug\src\specific\ToolsSpecific;

class CardRepository extends Repository
{
    private $cardEntity;
    private $configurationSpecific;
    private $query;
    private $toolsSpecific;

    protected $payplug;

    public function __construct()
    {
        $this->cardEntity = new CardEntity();
        $this->configurationSpecific = new ConfigurationSpecific();
        $this->query = new QueryRepository();
        $this->toolsSpecific = new ToolsSpecific();
        $this->setParams();
    }

    public static function factory()
    {
        return new CardRepository();
    }

    private function setParams()
    {
        $config = $this->configurationSpecific;
        $idCompany = $config->get('PAYPLUG_COMPANY_ID');
        $isSandbox = $config->get('PAYPLUG_SANDBOX_MODE');

        $this->cardEntity
            ->setAllowedBrand(['mastercard','visa'])
            ->setDefinition([])
            ->setFieldsRequired([])
            ->setFieldsSize([])
            ->setFieldsValidate([])
            ->setTable('payplug_card')
            ->setIdentifier('')
            ->setDefinition([
                'table' => $this->cardEntity->getTable(),
                'primary' => 'id_'.$this->cardEntity->getTable(),
                'fields' => [
                    /*
                     List of field types
                    ((int) instead of (string) since PS 1.5+)
                    (source : /classes/ObjectModel.php)
                        const TYPE_INT     = 1;
                        const TYPE_BOOL    = 2;
                        const TYPE_STRING  = 3;
                        const TYPE_FLOAT   = 4;
                        const TYPE_DATE    = 5;
                        const TYPE_HTML    = 6;
                        const TYPE_NOTHING = 7;
                        const TYPE_SQL     = 8;
                     */
                    'id_customer'   => ['type' => 1, 'validate' => 'isUnsignedId', 'required' => true],
                    'id_company'    => ['type' => 1, 'validate' => 'isUnsignedId', 'required' => true],
                    'is_sandbox'    => ['type' => 2, 'validate' => 'isBool'],
                    'id_card'       => ['type' => 3, 'validate' => 'isCleanHtml', 'required' => true],
                    'last4'         => ['type' => 3, 'validate' => 'isCleanHtml', 'size' => 4, 'required' => true],
                    'exp_month'     => ['type' => 3, 'validate' => 'isCleanHtml', 'size' => 4, 'required' => true],
                    'exp_year'      => ['type' => 3, 'validate' => 'isCleanHtml', 'size' => 4, 'required' => true],
                    'brand'         => ['type' => 3, 'validate' => 'isCleanHtml'],
                    'country'       => [
                        'type'          => 3,
                        'validate'      => 'isLanguageIsoCode',
                        'size'          => 3,
                        'required'      => false
                    ],
                    'metadata'      => ['type' => 3, 'validate' => 'isCleanHtml'],
                ]
            ]);

        if ($idCompany && (!empty($idCompany))) {
            $this->cardEntity->setIdCompany((int)$idCompany);
        }

        if ($isSandbox) {
            $this->cardEntity->setIsSandbox((bool)$isSandbox);
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

        $response = \Payplug\Card::delete($id_card);
        $json_answer = $response['httpResponse'];

        if (isset($json_answer['object']) && $json_answer['object'] == 'error') {
            return false;
        } else {
            $this->query
                ->delete()
                ->from(_DB_PREFIX_.$this->cardEntity->getTable())
                ->where(_DB_PREFIX_.$this->cardEntity->getTable().'.id_card = \'' . pSQL($id_card) . '\'')
                ->build()
            ;
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

        $req_card_id =
            $this->query
                ->select()
                ->fields('pc.id_card')
                ->from(_DB_PREFIX_ .'payplug_card', 'pc')
                ->where('pc.id_customer = ' . (int)$id_customer)
                ->where('pc.id_payplug_card = ' . (int)$id_payplug_card)
                ->where('pc.id_company = ' . (int)$id_company)
                ->where('pc.is_sandbox = ' . (int)$is_sandbox)
            ;

        $res_card_id = $req_card_id->build()[0]['id_card'];

        if (!$res_card_id) {
            return false;
        } else {
            return $res_card_id;
        }
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
        $cardsToDelete = $this->getCardsByCustomer($id_customer, false);
        if (isset($cardsToDelete) && !empty($cardsToDelete) && sizeof($cardsToDelete)) {
            foreach ($cardsToDelete as $card) {
                if (!$this->deleteCard($id_customer, $card['id_payplug_card'])) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @description
     * Get collection of cards
     *
     * @param int $id_customer
     * @param bool $active_only
     * @return array OR bool
     */
    public function getCardsByCustomer($id_customer, $active_only = false)
    {
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
            ->from(_DB_PREFIX_ .'payplug_card', 'pc')
            ->where('pc.id_customer = '.(int)$id_customer)
            ->where('pc.id_company = '.(int)$config->get('PAYPLUG_COMPANY_ID'.($is_sandbox ? '_TEST' : '')))
            ->where('pc.is_sandbox = '.(int)$is_sandbox)
        ;

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
                ->from(_DB_PREFIX_.$this->cardEntity->getTable())
                ->where('id_card = "' . $payment->card->id . '"')
                ->where('id_company = ' . (int)$company_id)
                ->where('is_sandbox = ' . (int)$is_sandbox)
                ->build()
        ;

        if ((isset($dbCard) && (!empty($dbCard)))) {
            return false;
        }

        // insert the new card in database
        $this->query
            ->insert()
            ->into(_DB_PREFIX_.$this->cardEntity->getTable())
            ->fields('id_customer') ->values((int)$customer_id)
            ->fields('id_company')  ->values((int)$company_id)
            ->fields('is_sandbox')  ->values((int)$is_sandbox)
            ->fields('id_card')     ->values(pSQL($payment->card->id))
            ->fields('last4')       ->values(pSQL($payment->card->last4))
            ->fields('exp_month')   ->values(pSQL($payment->card->exp_month))
            ->fields('exp_year')    ->values(pSQL($payment->card->exp_year))
            ->fields('brand')       ->values(pSQL($brand))
            ->fields('country')     ->values(pSQL($payment->card->country))
            ->fields('metadata')    ->values(pSQL(serialize($payment->card->metadata)))
        ;
        if (!$this->query->build()) {
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
            return false;
        }

        try {
            $table = $this->cardEntity->getTable();
            $this->query
                ->select()
                ->fields('id_card')
                ->from(_DB_PREFIX_.$table)
                ->where(_DB_PREFIX_.$table.'.id_'.$table.' = '.$idPayplugCard)
            ;

            $idCard = $this->query->build()[0]['id_card'];

            // Delete from API
            \Payplug\Card::delete($idCard);

            // Delete from our DB
            $this->query
                ->delete()
                ->from(_DB_PREFIX_.$table)
                ->where(_DB_PREFIX_.$table.'.id_card = \'' . pSQL($idCard) . '\'')
                ->build()
            ;

//            $this->tools->tool('redirect',$_SERVER['HTTP_REFERER']); exit;
            return '<script type="text/javascript">document.location.reload(true);</script>';
        } catch (Exception $e) {
            //@todo: add log
            if ($e->getCode() == '404') { // resource cant be found
                return parent::delete();
            }
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
        $fields['id_customer']  = is_null($card->getIdCustomer()) ? 0 : (int)($card->getIdCustomer());
        $fields['id_company']   = is_null($card->getIdCompany()) ? 0 : (int)($card->getIdCompany());
        $fields['is_sandbox']   = (int)$card->isIsSandbox();
        $fields['id_card']      = pSQL($card->getIdCard());
        $fields['last4']        = pSQL($card->getLast4());
        $fields['exp_month']    = pSQL($card->getExpMonth());
        $fields['exp_year']     = pSQL($card->getExpYear());
        $fields['brand']        = pSQL($card->getBrand());
        $fields['country']      = pSQL($card->getCountry());
        $fields['metadata']     = pSQL($card->getMetadata());

        return $fields;
    }

    /**
     * ## From classes/__PayPlugCard.php ##
     * Check if a card can be use
     *
     * @param int $month
     * @param int $year
     * @return array OR bool
     */
    public static function isValidExpiration($month, $year)
    {
        if ($month == null || $year == null) {
            return false;
        }

        if ($year < (int)date('Y') || ($year == (int)date('Y') && $month < (int)date('m'))) {
            return false;
        }

        return true;
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
            ->from(_DB_PREFIX_.$this->cardEntity->getTable())
            ->where('`id_customer` = '.((isset($customer->id) && !empty($customer->id)) ? $customer->id : $customer))
            ->where('`id_company` = ' . (int)$this->cardEntity->getIdCompany())
            ->where('`is_sandbox` = ' . (int)$this->cardEntity->getIsSandbox())
        ;

        $cards = $this->query->build();

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
     * @description Return successflull deleted card, called in front/cards.php
     * @return mixed
     */
    public function deleteCardMessage()
    {
        return $this->l('Card sucessfully deleted.');
    }
}
