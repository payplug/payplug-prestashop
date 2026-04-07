<?php

namespace PayPlug\Tests\utilities\helpers\LinkHelper;

use PayPlug\src\utilities\helpers\LinkHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group helper
 * @group link_helper
 */
class getAdminReturnUrlTest extends TestCase
{
    public function setUp()
    {
        \Mockery::mock('alias:\Configuration')
            ->shouldReceive('get')
            ->andReturn(false);
    }

    /**
     * @description Returns false when Configuration has no stored URL.
     *              Configuration::get returns false for missing keys.
     */
    public function testReturnsFalseWhenNoUrlStored()
    {
        $result = LinkHelper::getAdminReturnUrl();

        $this->assertFalse($result);
    }

    public function tearDown()
    {
        \Mockery::close();
    }
}
