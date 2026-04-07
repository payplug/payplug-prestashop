<?php

namespace PayPlug\Tests\utilities\helpers\LinkHelper;

use PayPlug\src\utilities\helpers\LinkHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group helper
 * @group link_helper
 */
class getStoredOAuthStateTest extends TestCase
{
    public function setUp()
    {
        \Mockery::mock('alias:\Configuration')
            ->shouldReceive('get')
            ->with('PAYPLUG_OAUTH_STATE')
            ->andReturn(false);
    }

    /**
     * @description Returns empty string when no state has been stored.
     *              Configuration::get returns false for missing keys,
     *              getStoredOAuthState casts to string.
     */
    public function testReturnsEmptyStringWhenNoStateStored()
    {
        $result = LinkHelper::getStoredOAuthState();

        $this->assertSame('', $result);
    }

    public function tearDown()
    {
        \Mockery::close();
    }
}
