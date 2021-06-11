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

namespace PayPlug\tests\repositories\OneyRepository;

/**
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class IsAvailableWithoutFeesTest extends BaseOneyRepository
{
    public function invalidIsoDataProvider()
    {
        yield ['IT'];
        yield [42];
        yield [null];
        yield [['array', 'array', 'array', 'array']];
    }
    /**
     * @dataProvider invalidIsoDataProvider
     */
    public function testWithInvalidIsoCode($iso_code)
    {
        $this->assertSame(
            false,
            $this->repo->isAvailableWithoutFees($iso_code)
        );
    }

    public function testWithValidIsoCode()
    {
        $iso_code = 'fr';
        $this->assertSame(
            true,
            $this->repo->isAvailableWithoutFees($iso_code)
        );
    }
}
