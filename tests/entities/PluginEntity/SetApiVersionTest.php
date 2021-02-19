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



use PayPlug\src\entities\PluginEntity;
use PayPlug\src\exceptions\BadParameterException;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group plugin
 * @group plugin_entity
 */
final class SetApiVersionTest extends TestCase
{
    protected $plugin;

    protected function setUp()
    {
        $this->plugin = new PluginEntity();
        $this->plugin->setApiVersion('2021-01-01');
    }

    public function testUpdateApiVersion()
    {
        $this->plugin->setApiVersion('1920-12-31');
        $this->assertSame(
            '1920-12-31',
            $this->plugin->getApiVersion()
        );
    }

    public function testReturnLoggerEntity()
    {
        $this->assertInstanceOf(
            PluginEntity::class,
            $this->plugin->setApiVersion('1920-12-31')
        );
    }

    /**
     * @group entity_exception
     * @group plugin_exception
     * @group plugin_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotAString()
    {
        $this->expectException(BadParameterException::class);
        $this->plugin->setApiVersion('wrong_api_version');
    }

    /**
     * @group entity_exception
     * @group plugin_exception
     * @group plugin_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotWellFormatted()
    {
        $this->expectException(BadParameterException::class);
        $this->plugin->setApiVersion('1er Janvier 1970');
    }
}
