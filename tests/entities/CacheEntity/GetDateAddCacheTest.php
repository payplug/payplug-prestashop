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
use PayPlug\src\models\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 *
 * @internal
 * @coversNothing
 */
final class GetDateAddCacheTest extends TestCase
{
    protected $cache;

    protected function setUp()
    {
        $this->cache = new CacheEntity();
        $this->cache->setDateAdd('2021-12-31 23:59:42');
    }

    public function testReturnDateAdd()
    {
        $this->assertSame(
            '2021-12-31 23:59:42',
            $this->cache->getDateAdd()
        );
    }

    public function testDateAddIsAString()
    {
        $this->assertTrue(
            is_string($this->cache->getDateAdd())
        );
    }

    public function testDateAddHaveAValidDatetimeFormat()
    {
        $this->assertRegExp(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $this->cache->getDateAdd()
        );
    }
}
