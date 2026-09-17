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
if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\src\application\adapter\PrestashopAdapter17;

/**
 * @description Receive the UHF hosted-fields tokenization result from checkout.
 *              Validates the payload; actual payment-resource creation is handled
 *              by a follow-up ticket ("Branchement paiement / 3DS sur UPC").
 */
class PayplugUhfModuleFrontController extends ModuleFrontController
{
    private $cartAdapter;
    private $dependenciesClass;
    private $orderAdapter;
    private $prestashopAdapter;
    private $toolsAdapter;

    public function __construct()
    {
        parent::__construct();
        $this->dependenciesClass = new PayPlug\classes\DependenciesClass();
        $this->cartAdapter = $this->dependenciesClass->getPlugin()->getCart();
        $this->orderAdapter = $this->dependenciesClass->getPlugin()->getOrder();
        $this->toolsAdapter = $this->dependenciesClass->getPlugin()->getTools();
        $this->prestashopAdapter = $this->dependenciesClass->loadAdapterPresta();
    }

    public function postProcess()
    {
        header('Content-Type: application/json');

        $hfToken = $this->toolsAdapter->tool('getValue', 'hfToken');
        $selectedBrand = $this->toolsAdapter->tool('getValue', 'selectedBrand');

        if (!is_string($hfToken) || !$hfToken || !is_string($selectedBrand) || !$selectedBrand) {
            exit(json_encode([
                'result' => false,
                'message' => 'Missing hfToken or selectedBrand',
            ]));
        }

        if (!in_array(strtolower($selectedBrand), PrestashopAdapter17::HOSTED_FIELDS_ACCEPTED_BRANDS, true)) {
            exit(json_encode([
                'result' => false,
                'message' => $this->dependenciesClass->l('This card brand is not supported.', 'uhf'),
            ]));
        }

        if (!$this->dependenciesClass->configClass->isValidFeature('feature_hosted_fields')) {
            exit(json_encode([
                'result' => false,
                'message' => 'This payment method is not available',
            ]));
        }

        $id_cart = (int) $this->toolsAdapter->tool('getValue', 'id_cart');
        if ($id_cart <= 0) {
            exit(json_encode([
                'result' => false,
                'message' => 'Missing or invalid id_cart',
            ]));
        }

        // Ownership check runs on the already-validated $id_cart int, before the
        // DB-hydrating Cart fetch below, to short-circuit mismatched requests
        // with zero DB I/O (ObjectModel always sets ->id from the constructor
        // argument regardless of whether a matching row exists, so this is
        // equivalent to comparing $cart->id afterwards).
        if ($id_cart !== (int) $this->context->cart->id) {
            exit(json_encode([
                'result' => false,
                'message' => 'Invalid cart',
            ]));
        }

        $cart = $this->cartAdapter->get($id_cart);

        // Keyed off the cart's own currency rather than the request context's.
        $currency = $this->dependenciesClass->getPlugin()->getCurrency()->getCurrency((int) $cart->id_currency);
        if (!$this->prestashopAdapter || !$this->prestashopAdapter->isHostedFieldsIdentifierConfigured($currency->iso_code)) {
            exit(json_encode([
                'result' => false,
                'message' => 'This payment method is not available',
            ]));
        }

        $order = $this->orderAdapter->get((int) $this->orderAdapter->getIdByCartId((int) $cart->id));
        $order_exists = $this->dependenciesClass
            ->getValidators()['order']
            ->isCreated($order, (int) $cart->id);

        if ($order_exists['result']) {
            exit(json_encode([
                'result' => false,
                'message' => $this->dependenciesClass->l('The transaction was not completed and your card was not charged.', 'uhf'),
            ]));
        }

        // Extension point for the follow-up ticket ("Branchement paiement / 3DS sur UPC"):
        // create/confirm the UPC payment resource from $hfToken / $selectedBrand here,
        // then return the real return_url. For this ticket, acknowledge receipt only.
        $save_card = (bool) $this->toolsAdapter->tool('getValue', 'save_card');
        // Kept as a variable (not logged in plaintext - it's PII) for the follow-up
        // ticket ("Branchement paiement / 3DS sur UPC"), which will need the actual
        // value to build the payment resource.
        $cardholderName = $this->toolsAdapter->tool('getValue', 'cardholderName');
        $cardholderName = is_string($cardholderName) ? preg_replace('/[\r\n]+/', ' ', $cardholderName) : '';
        $this->dependenciesClass->getPlugin()->getLogger()->addLog(
            'PayplugUhfModuleFrontController::postProcess() - Received hfToken for cart '
            . (int) $cart->id . ', brand=' . $selectedBrand . ', save_card=' . ($save_card ? '1' : '0')
            . ', cardholderName=' . ('' !== $cardholderName ? 'set' : 'empty')
        );

        exit(json_encode([
            'result' => true,
        ]));
    }
}
