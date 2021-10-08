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

namespace PayPlug\src\specific;

use Language;
use PayPlug\classes\MyLogPHP;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use Tab;
use Tools;

class PrestashopSpecific17
{
    public $payplug;
    private $contextSpecific;

    public function __construct($payplug)
    {
        $this->payplug = $payplug;
        $this->contextSpecific = (new ContextSpecific())->getContext();
    }

    public function displayHeader()
    {
        $this->payplug->mediaClass->addCSSRC(__PS_BASE_URI__ . 'modules/payplug/views/css/front.css');
        $this->payplug->mediaClass->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/utilities.js');
        $this->payplug->mediaClass->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/front.js');
    }

    public function displayPaymentOption($payment_options)
    {
        $paymentOptions = [];
        foreach ($payment_options as $payment_option) {
            $payment_method = $payment_option['name'];
            $paymentOption = new PaymentOption();
            if (isset($payment_option['expiry_date_card'])) {
                $payment_option['callToActionText'] .= ' - '. $payment_option['expiry_date_card'];
            }
            $paymentOption
                ->setLogo($payment_option['logo'])
                ->setCallToActionText($payment_option['callToActionText'])
                ->setAction($payment_option['action'])
                ->setModuleName($payment_option['moduleName'])
                ->setInputs($payment_option['inputs']);

            // load oney schedule on e page loading
            if ($payment_method == 'oney' && $payment_option['is_optimized']) {
                try {
                    $payment_schedule = $this->payplug->oney->getOneyPaymentOptionsList(
                        $payment_option['amount'],
                        $payment_option['iso_code']
                    );
                } catch (\Exception $e) {
                    // todo: set a permanent log
                    $payment_schedule = false;
                }

                if ($payment_schedule) {
                    $schedules = $this->payplug->oney->displayOneySchedule(
                        $payment_schedule[$payment_option['type']],
                        $payment_option['amount']
                    );
                    $payment_option['additionalInformation'] = $schedules;
                }
            }

            if (isset($payment_option['additionalInformation'])) {
                $paymentOption->setAdditionalInformation($payment_option['additionalInformation']); // Échéanciers Oney
            }

            $paymentOptions[] = $paymentOption;
        }

        return $paymentOptions;
    }

    // todo: set Tab install process in a specific
    public function installTab()
    {
        $installed = true;
        $moduleName = $this->payplug->name;

        // Install tab AdminPayPlug
        if (!Tab::getIdFromClassName('AdminPayPlug')) {
            $translationsAdminPayPlug = [
                'en' => 'PayPlug',
                'gb' => 'PayPlug',
                'it' => 'PayPlug',
                'fr' => 'PayPlug'
            ];
            $tab = new Tab();
            foreach (Language::getLanguages(false) as $language) {
                $id_lang = (int)$language['id_lang'];
                $iso_code = Tools::strtolower($language['iso_code']);
                if (isset($translationsAdminPayPlug[$iso_code])) {
                    $tab->name[$id_lang] = $translationsAdminPayPlug[$iso_code];
                } else {
                    $tab->name[$id_lang] = $translationsAdminPayPlug['en'];
                }
            }

            $tab->class_name = 'AdminPayPlug';
            $tab->module = $moduleName;

            $installed = $installed && $tab->save();
        }

        // Install tab AdminPayPlugInstallment
        if (!Tab::getIdFromClassName('AdminPayPlugInstallment')) {
            $translationsAdminPayPlugInstallment = [
                'en' => 'Installment Plans',
                'gb' => 'Installment Plans',
                'it' => 'Pagamenti frazionati',
                'fr' => 'Paiements en plusieurs fois'
            ];
            $tab = new Tab();
            foreach (Language::getLanguages(false) as $language) {
                $id_lang = (int)$language['id_lang'];
                $iso_code = Tools::strtolower($language['iso_code']);
                if (isset($translationsAdminPayPlugInstallment[$iso_code])) {
                    $tab->name[$id_lang] = $translationsAdminPayPlugInstallment[$iso_code];
                } else {
                    $tab->name[$id_lang] = $translationsAdminPayPlugInstallment['en'];
                }
            }
            $tab->class_name = 'AdminPayPlugInstallment';
            $tab->module = $moduleName;
            $tab->id_parent = Tab::getIdFromClassName('AdminPayPlug');

            $installed = $installed && $tab->save();
        }

        return $installed;
    }

    // todo: set Tab uninstall process in a specific
    public function uninstallTab()
    {
        $flag = true;

        $idTab = Tab::getIdFromClassName('AdminPayPlug');
        if ($idTab) {
            $tab = new Tab($idTab);
            $flag = $flag && $tab->delete();
            unset($idTab);
        }

        $idTab = Tab::getIdFromClassName('AdminPayPlugInstallment');
        if ($idTab) {
            $tab = new Tab($idTab);
            $flag = $flag && $tab->delete();
            unset($idTab);
        }

        return $flag;
    }

    /**
     * @description Link to order by order state
     *
     * @param int $order_state
     * @return string
     */
    public function getOrdersByStateLink($order_state)
    {
        $link = $this->contextSpecific->link->getAdminLink(
            'AdminOrders',
            true,
            [],
            ['order[filters][osname]' => $order_state]
        );
        return $link;
    }
}
