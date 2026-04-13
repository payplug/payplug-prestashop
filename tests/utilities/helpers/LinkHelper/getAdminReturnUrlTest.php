<?php

namespace PayPlug\Tests\utilities\helpers\LinkHelper;

use PayPlug\src\utilities\helpers\LinkHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group helper
 * @group link_helper
 *
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class getAdminReturnUrlTest extends TestCase
{
    public function tearDown()
    {
        \Mockery::close();
    }

    /**
     * @description Returns false when Configuration has no stored URL.
     *              Configuration::get returns false for missing keys.
     */
    public function returnsFalseWhenNoUrlStored() // todo: test disable, add "test" in prefix of the method name to run the test
    {
        $mock = \Mockery::mock('alias:\Configuration');
        $mock->shouldReceive('get')
            ->with('PAYPLUG_ADMIN_RETURN_URL')
            ->once()
            ->andReturn(false);

        $result = LinkHelper::getAdminReturnUrl();

        $this->assertFalse($result);
    }
}
