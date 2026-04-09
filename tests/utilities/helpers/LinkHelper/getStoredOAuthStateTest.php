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
class getStoredOAuthStateTest extends TestCase
{
    public function tearDown()
    {
        \Mockery::close();
    }

    /**
     * @description Returns empty string when no state has been stored.
     *              Configuration::get returns false for missing keys,
     *              getStoredOAuthState casts to string.
     */
    public function returnsEmptyStringWhenNoStateStored() // todo: test disable, add "test" in prefix of the method name to run the test
    {
        $mock = \Mockery::mock('alias:\Configuration');
        $mock->shouldReceive('get')
            ->with('PAYPLUG_OAUTH_STATE')
            ->once()
            ->andReturn(false);

        $result = LinkHelper::getStoredOAuthState();

        $this->assertSame('', $result);
    }
}
