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
use Payplug\InstallmentPlan;
use Payplug\Payment;
use PayPlug\src\entities\PaymentEntity;
use PayPlug\src\specific\CartSpecific;

class PaymentRepository extends Repository
{
    private $apiPayment;
    private $cartSpecific;
    private $logger;
    private $paymentEntity;
    private $query;

    public function __construct()
    {
        $this->setParams();
    }

    public function setParams()
    {
        $this->cartSpecific = CartSpecific::factory();
        $this->query = QueryRepository::factory();
        $this->paymentEntity = new PaymentEntity();
        $this->setLogger();
    }

    /**
     * @description Set the logger
     */
    public function setLogger()
    {
        $this->logger = new LoggerRepository();
        $this->logger->setParams(['process' => 'payment']);
    }

    /**
     * @description Create payment / installment
     * @param array $paymentDetails
     * @return bool|\Payplug\Resource\InstallmentPlan|\Payplug\Resource\Payment|null
     * @throws ConfigurationNotSetException
     */
    public function createPayment($paymentDetails)
    {
        if (!$paymentDetails) {
            $this->logger->addLog('[createPayment] $paymentDetails is null', 'error');
            return false;
        }

        if (!$paymentDetails['paymentTab']) {
            $this->logger->addLog('[createPayment] $paymentDetails[\'paymentTab\'] is null', 'error');
            return false;
        }

        if ($paymentDetails['paymentMethod'] == 'standard') {
            $apiPayment = Payment::create($paymentDetails['paymentTab']);
            $this->paymentEntity->setApiPayment($apiPayment);
        } elseif ($paymentDetails['paymentMethod'] == 'installment') {
            $apiPayment =  InstallmentPlan::create($paymentDetails['paymentTab']);
            $this->paymentEntity->setApiPayment($apiPayment);
        }

        switch ($paymentDetails['paymentMethod']):
            case 'standard':
            case 'installment':
                $this->apiPayment = $this->paymentEntity->getApiPayment();

                if ($this->apiPayment->failure == true && !empty($this->apiPayment->failure->message)) {
                    return $this->displayErrorPayment($this->apiPayment->failure->message);
                }

                // We can now hydrate the pay_id / inst_id
                if (!$paymentDetails['paymentId'] && isset($this->apiPayment->id)) {
                    $paymentDetails['paymentId'] = $this->apiPayment->id;
                } else {
                    $this->logger->addLog('[createPayment] $this->apiPayment->id is null / not set', 'error');
                    return $this->displayErrorPayment();
                }

                if (!$paymentDetails['paymentReturn'] && isset($this->apiPayment->hosted_payment->payment_url)) {
                    $paymentDetails['paymentReturn'] = $this->apiPayment->hosted_payment->payment_url;
                } else {
                    $this->logger->addLog('[createPayment] hosted_payment->payment_url is null / not set', 'error');
                    return $this->displayErrorPayment();
                }

                return($paymentDetails);
                break;
            default:
                $this->logger->addLog('[createPayment] $paymentDetails[\'paymentMethod\'] is null', 'error');
                return false;
        endswitch;
    }

    /**
     * @description Check if payment created < 1min in DB
     * @param $idCart
     * @return bool
     * @throws Exception
     */
    public function checkTimeoutPayment($idCart)
    {
        if (!$idCart) {
            $this->logger->addLog('[checkTimeoutPayment] the $idCart parameter is null', 'error');
            return false;
        }

        if (!is_int($idCart)) {
            $this->logger->addLog('[checkTimeoutPayment] the $idCart parameter is not an integer', 'error');
            return false;
        }

        $dateStored = $this->checkPaymentTable($idCart)['date_upd'];

        $date = new \DateTime($dateStored);
        $date2 = new \DateTime('now');

        if ($date->diff($date2)->y !== 0 ||
            $date->diff($date2)->d !== 0 ||
            $date->diff($date2)->h !== 0 ||
            $date->diff($date2)->i > 0 ||
            $date->diff($date2)->s > 59) {
            // Plus d'une minute
            return false;
        } else {
            // Moins d'une minute
            return true;
        }
        exit;
    }

    /**
     * @description Display an error if problem in creation
     * @param string $errorMessage
     * @return array
     */
    public function displayErrorPayment($errorMessage)
    {
        if (!$errorMessage) {
            $errorMessage = $this->l('The transaction was not completed and your card was not charged.');
        }

        if (!is_string($errorMessage)) {
            $this->logger->addLog('[displayErrorPayment] The error message is not a string.');
            return false;
        }

            $this->setPaymentErrorsCookie([$errorMessage]);

            return [
                'result' => false,
                'response' => $errorMessage,
            ];
    }

    /**
     * @description Insert payment with all details in table
     */
    public function insertPaymentTable($paymentDetails)
    {
        if (!isset($paymentDetails)) {
            $this->logger->addLog('[insertPaymentTable] The parameter $paymentDetails is null', 'error');
            return false;
        }

        if (!is_array($paymentDetails)) {
            $this->logger->addLog('[insertPaymentTable] The parameter $paymentDetails is not an array', 'error');
            return false;
        }

        $paymentDate = date('Y-m-d H:i:s');
        $paymentDetails['cart']->date_upd = null;
        $cartHash = hash('sha256',
            $paymentDetails['paymentMethod'].json_encode($paymentDetails['cart'])
        );

        $this->query
            ->insert()
            ->into(_DB_PREFIX_ . 'payplug_payment')
            ->fields('id_payment')      ->values(pSQL($paymentDetails['paymentId']))
            ->fields('payment_method')  ->values(pSQL($paymentDetails['paymentMethod']))
            ->fields('payment_return')  ->values(pSQL($paymentDetails['paymentReturn']))
            ->fields('id_cart')         ->values(pSQL($paymentDetails['cartId']))
            ->fields('cart_hash')       ->values(pSQL($cartHash))
            ->fields('is_deferred')     ->values(pSQL($paymentDetails['isDeferred']))
            ->fields('is_embedded')     ->values(pSQL($paymentDetails['isEmbedded']))
            ->fields('is_mobile_device')->values(pSQL($paymentDetails['isMobileDevice']))
            ->fields('date_upd')        ->values(pSQL($paymentDate))
        ;
        if (!$this->query->build()) {
            $this->logger->addLog('[insertPaymentCart] Unable to flush DB (build method)', 'error');
            return false;
        }

        return $paymentDetails;
    }

    /**
     * @description Check if existing payment or installment hashed
     * @param integer $idCart
     * @return bool|array
     */
    public function checkPaymentTable($idCart)
    {
        if (!$idCart) {
            $this->logger->addLog('[checkPaymentTable] the $idCart parameter is null', 'error');
            return false;
        }

        if (!is_int($idCart)) {
            $this->logger->addLog('[checkPaymentTable] the $idCart parameter is not an integer', 'error');
            return false;
        }

        $reqCheck = $this->query
            ->select()
            ->fields('*')
            ->from(_DB_PREFIX_ .'payplug_payment')
            ->where('id_cart = ' . (int)$idCart);

        $resCheck = $reqCheck->build();

        if (!$resCheck) {
            return false;
        } else {
            return end($resCheck);
        }
    }

    /**
     * @description Update hash and payment id in Payplug Payment Cart table
     * @param array $paymentDetails
     * @return array
     */
    public function updatePaymentTable($paymentDetails)
    {
        $paymentDate = date('Y-m-d H:i:s');
        $paymentDetails['cart']->date_upd = null;
        $cartHash = hash('sha256',
            $paymentDetails['paymentMethod'].json_encode($cart = $paymentDetails['cart']));

        $table = _DB_PREFIX_ .'payplug_payment';

        $this->query
            ->update()
            ->table($table)
            ->set($table.'.id_payment =         \''.pSQL($paymentDetails['paymentId']).'\'')
            ->set($table.'.payment_method =     \''.pSQL($paymentDetails['paymentMethod']).'\'')
            ->set($table.'.payment_return =     \''.pSQL($paymentDetails['paymentReturn']).'\'')
            ->set($table.'.cart_hash =          \''.pSQL($cartHash).'\'')
            ->set($table.'.is_deferred =        \''.pSQL($paymentDetails['isDeferred']).'\'')
            ->set($table.'.is_embedded =        \''.pSQL($paymentDetails['isEmbedded']).'\'')
            ->set($table.'.is_mobile_device =   \''.pSQL($paymentDetails['isMobileDevice']).'\'')
            ->set($table.'.is_paid =            \''.pSQL($paymentDetails['isPaid']).'\'')
            ->set($table.'.date_upd =           \''.pSQL($paymentDate).'\'')
            ->where($table.'.id_cart =          '.(int)$paymentDetails['cartId'])
        ;

        if (!$this->query->build()) {
            $this->logger->addLog('[updatePaymentTable] Unable to fetch the query on DB');
            return false;
        }

        return $paymentDetails;
    }

    public function checkHash($paymentDetails)
    {
        if (!isset($paymentDetails)) {
            $this->logger->addLog('[checkHash] The parameter $paymentDetails is null', 'error');
            return false;
        }

        if (!is_array($paymentDetails)) {
            $this->logger->addLog('[checkHash] The parameter $paymentDetails is not an array', 'error');
            return false;
        }

        $paymentStored = $this->checkPaymentTable($paymentDetails['cartId']);
        $paymentDetails['cart']->date_upd = null;

        $cartHash = hash('sha256',
            $paymentDetails['paymentMethod'].json_encode($paymentDetails['cart']));

        /*
         * Si le hash est strictement identique avec même mode de paiement : on est OK
         */
        if ($paymentStored['cart_hash'] === $cartHash && ($paymentStored['payment_method'] == $paymentDetails['paymentMethod'])) {
            return $paymentDetails;
        /*
         * Si le hash et mode de paiement différents
         * OU
         * Si le hach est identique mais un autre moyen de paiement a été sélectionné entre temps
         * OU
         * Si le hash est différent avec le même mode de paiement
         * => On met à jour le paiement avec le nouveau pay_id / inst_id
         */
        } else
//        (
//            (($paymentStored['cart_hash'] !== $cartHash) && ($paymentStored['payment_method'] !== $paymentDetails['paymentMethod']))
//            ||
//            (($paymentStored['cart_hash'] === $cartHash) && ($paymentStored['payment_method'] !== $paymentDetails['paymentMethod']))
//            ||
//            ($paymentStored['cart_hash'] !== $cartHash) && ($paymentStored['payment_method'] == $paymentDetails['paymentMethod'])
//        )
        {
            $paymentDetails = $this->createPayment($paymentDetails);
            return $this->updatePaymentTable($paymentDetails);
        }
    }

    public function getPaymentReturn($paymentDetails)
    {
        if (!$paymentDetails['paymentReturn']) {
            $paymentStored = $this->checkPaymentTable($paymentDetails['cartId']);
            $paymentDetails['paymentReturn'] = $paymentStored['payment_return'];
        }

        switch ($paymentDetails['paymentMethod']) {
//                    case 'oneclick':
//                        $redirect = $payment->is_paid;
//                        if (!$redirect && $options['is_deferred']) {
//                            $redirect = (bool)$payment->authorization->authorized_at;
//                        }
//                        $paymentReturn = [
//                            'result' => true,
//                            'embedded' => true,
//                            'redirect' => $redirect, // force `true` we are in 3DS 1
//                            'return_url' => $redirect ?
//                                $payment->hosted_payment->return_url : $payment->hosted_payment->payment_url,
//                        ];
//                        break;
//                    case 'oney':
//                        $paymentReturn = [
//                            'result' => 'new_card',
//                            'embedded' => false,
//                            'redirect' => true,
//                            'return_url' => $payment->hosted_payment->payment_url,
//                        ];
//                        break;
                    case 'standard':
                    case 'installment':
                    default:
                        $paymentReturn = [
                            'result' => 'new_card',
                            'embedded' => $paymentDetails['isEmbedded'] && !$paymentDetails['isMobileDevice'],
                            'redirect' => $paymentDetails['isMobileDevice'],
                            'return_url' => $paymentDetails['paymentReturn']
                        ];
                        break;
                }
                return $paymentReturn;
            }
    }