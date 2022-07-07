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



use PayPlug\src\models\entities\CardEntity;
use PayPlug\src\models\entities\PluginEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group plugin
 * @group plugin_entity
 */
final class GetApiUrlTest extends TestCase
{
    // @todo : ApiClass
//    protected $plugin;
//
//    protected function setUp()
//    {
//        $this->plugin = new PluginEntity();
//        $this->plugin->setApiUrl('https://api-qa.payplug.com');
//    }
//
//    public function testReturnAnApiUrl()
//    {
//        $this->assertSame(
//            'https://api-qa.payplug.com',
//            $this->plugin->getApiUrl()
//        );
//    }
//
//    public function testApiUrlIsAString()
//    {
//        $this->assertTrue(
//            is_string($this->plugin->getApiUrl())
//        );
//    }
//
//    public function testApiUrlHaveAValidFormat()
//    {
//        $this->assertRegExp(
//            '/http(s?):\/\/api(-\w+)?.payplug.(com|test)/',
//            $this->plugin->getApiUrl()
//        );
//    }
}
