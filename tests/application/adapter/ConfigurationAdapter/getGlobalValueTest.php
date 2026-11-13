<?php

namespace PayPlug\tests\application\adapter\ConfigurationAdapter;

use PayPlug\src\application\adapter\ConfigurationAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Runs in its own process: the PrestaShop Configuration class is stubbed globally.
 *
 * @group unit
 * @group adapter
 */
class getGlobalValueTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testReadsTheGlobalValueFromTheCms()
    {
        eval('class Configuration
        {
            public static $calls = [];

            public static function getGlobalValue($key)
            {
                self::$calls[] = $key;

                return "value";
            }
        }');

        $this->assertSame('value', (new ConfigurationAdapter())->getGlobalValue('PAYPLUG_KEY'));
        $this->assertSame(['PAYPLUG_KEY'], \Configuration::$calls);
    }
}
