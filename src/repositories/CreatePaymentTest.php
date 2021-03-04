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

use PayPlug\src\entities\OneyEntity;
use PayPlug\src\repositories\OneyRepository;
use PayPlug\src\specific\AddressSpecific;
use PayPlug\src\specific\CarrierSpecific;
use PayPlug\src\specific\CartSpecific;
use PayPlug\src\specific\ContextSpecific;
use PayPlug\src\specific\CountrySpecific;
use PayPlug\tests\repositories\OneyRepository\BaseTest;

/**
 * @group unit
 * @group repository
 * @group payment
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
final class CreatePaymentTest extends
{

    public function setUp()
    {
        $this->email = 'mock@payplug.com';

        parent::setUp();

        $this->repo = new OneyRepository(
            $this->cache,
            $this->logger,
            new AddressSpecific(),
            new CartSpecific(),
            new CarrierSpecific(),
            $this->config,
            new ContextSpecific(),
            new CountrySpecific(),
            $this->tools,
            $this->validate,
            new OneyEntity(),
            $this->myLogPhp,
            $this->payplug
        );
    }

}
