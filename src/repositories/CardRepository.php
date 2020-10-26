<?php

/**
 * 2013 - 2020 PayPlug SAS
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
 * @copyright 2013 - 2020 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\repositories;

use PayPlug\src\specific\ConfigurationSpecific;

class CardRepository
{
    private $configurationSpecific;
    private $plugin;
    private $query;

    public function __construct()
    {
        $this->configurationSpecific = new ConfigurationSpecific();
//        $this->plugin = (new \Payplug())->getPlugin();
        $this->query = new QueryRepository();
    }

    /**
     * Delete card
     *
     * @param int $id_customer
     * @param int $id_payplug_card
     * @param string $api_key
     * @return bool
     */
    public function deleteCard($id_customer, $id_payplug_card, $api_key)
    {
        $config = $this->configurationSpecific;
        $is_sandbox = (int)$config->get('PAYPLUG_SANDBOX_MODE');
        $id_company = (int)$config->get('PAYPLUG_COMPANY_ID' . ($is_sandbox ? '_TEST' : ''));
        $id_card = $this->getCardId($id_customer, $id_payplug_card, $id_company);
        $url = $this->plugin->getApiUrl() . '/v1/cards/' . $id_card;
        $curl_version = curl_version();

        $process = curl_init($url);
        curl_setopt($process, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $api_key));
        curl_setopt($process, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($process, CURLINFO_HEADER_OUT, true);
        // CURL const are in uppercase
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, true);
        # >= 7.26 to 7.28.1 add a notice message for value 1 will be remove
        curl_setopt(
            $process,
            CURLOPT_SSL_VERIFYHOST,
            (version_compare($curl_version['version'], '7.21', '<') ? true : 2)
        );
        curl_setopt($process, CURLOPT_CAINFO, realpath(dirname(__FILE__) . '/cacert.pem')); //work only wiht cURL 7.10+
        $error_curl = curl_errno($process);

        curl_close($process);

        // if no error
        if ($error_curl == 0) {
            $this->query
                ->delete()
                ->from(_DB_PREFIX_ . 'payplug_card')
                ->where(_DB_PREFIX_ . 'payplug_card.id_card = \'' . pSQL($id_card) . '\'')
                ;
        } else {
            return false;
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
     * @param string $api_key
     * @return bool
     */
    public function deleteCards($id_customer)
    {
        $config = $this->configurationSpecific;
        $test_api_key = $config->get('PAYPLUG_TEST_API_KEY');
        $live_api_key = $config->get('PAYPLUG_LIVE_API_KEY');
        $cardsToDelete = $this->getCardsByCustomer($id_customer, false);
        if (isset($cardsToDelete) && !empty($cardsToDelete) && sizeof($cardsToDelete)) {
            foreach ($cardsToDelete as $card) {
                $api_key = $card['is_sandbox'] == 1 ? $test_api_key : $live_api_key;
                if (!$this->deleteCard($id_customer, $card['id_payplug_card'], $api_key)) {
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
            ->where('pc.id_customer = ' . (int)$id_customer)
            ->where('pc.id_company = ' . (int)$config->get('PAYPLUG_COMPANY_ID' . ($is_sandbox ? '_TEST' : '')))
            ->where('pc.is_sandbox = ' . (int)$is_sandbox)
        ;

        $res_payplug_card = $this->query->build();

        if (!$res_payplug_card) {
            return [];
        } else {
            foreach ($res_payplug_card as $key => &$value) {
                if ((int)$value['exp_year'] < (int)date('Y')
                    || ((int)$value['exp_year'] == (int)date('Y') && (int)$value['exp_month'] < (int)date('m'))) {
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
}