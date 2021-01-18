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

use PayPlug\tests\mock\CartMock;
use PHPUnit\Framework\TestCase;

/**
 * @group repository
 * @group oney
 * @group oney_repository
 */
final class isValidOneyCartTest extends TestCase
{
    protected $minQuantity;
    protected $maxQuantity;
    protected $products;
    protected $nbProducts;

    public function setUp()
    {
        $this->minQuantity = 1;
        $this->maxQuantity = 1000;
        $this->products = CartMock::getProducts();

        $this->nbProducts = 0;
        foreach($this->products as $item) {
            $this->nbProducts += $item['cart_quantity'];
        }
    }

    public function testProductNumber()
    {
        $this->assertSame(
            10,
            $this->nbProducts
        );
    }

    public function testProductNumberIsInt()
    {
        $this->assertTrue(
            is_int($this->nbProducts)
        );
    }

    public function testIsMinQuantityValid()
    {
        $condition = $this->nbProducts >= $this->minQuantity;
        $this->assertTrue($condition);
    }

    public function testIsMaxQuantityValid()
    {
        $condition = $this->nbProducts <= $this->maxQuantity;
        $this->assertTrue($condition);
    }
}
