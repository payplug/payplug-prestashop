<?php
/**
 * 2013 - 2023 Payplug SAS
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
 * Do not edit or add to this file if you wish to upgrade Payplug module to newer
 * versions in the future.
 *
 * @author    Payplug SAS
 * @copyright 2013 - 2023 Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */

namespace PayPlug\classes;

class CardClass
{
    private $card;
    private $constant;
    private $dependencies;
    private $query;
    private $sql;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->card = $this->dependencies->getPlugin()->getCard();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->query = $this->dependencies->getPlugin()->getQuery();
        $this->sql = $this->dependencies->getPlugin()->getSql();
    }

    /**
     * @description Delete saved cards when uninstalling module
     * todo: move this method in CardRepository
     *
     * @throws Exception
     *
     * @return bool
     */
    public function uninstallCards()
    {
        if ($this->sql->checkExistingTable($this->dependencies->name . '_card', 1)) {
            $cards = $this->dependencies->getRepositories()['card']->getAll();

            if ($cards) {
                foreach ($cards as $card) {
                    $id_customer = $card['id_customer'];
                    $id_payplug_card = $card['id_payplug_card'];
                    if (!$this->card->deleteCard((int) $id_customer, (int) $id_payplug_card)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
