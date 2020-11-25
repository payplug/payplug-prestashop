<?php declare(strict_types=1);
/**
 * 2013 - 2020 PayPlug SAS
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
 * @copyright 2013 - 2020 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

use PayPlug\src\entities\CacheEntity;
use PayPlug\src\exceptions\BadParameterException;
use PHPUnit\Framework\TestCase;

final class SetDefinitionTest extends TestCase
{
    protected $cache;
    protected $definition;
    protected $definition_alt;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->definition = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->definition_alt = [
            'keyA' => 'valueA',
            'keyB' => 'valueB',
            'keyC' => 8,
        ];
        $this->cache->setDefinition($this->definition);
    }

    public function testUpdateDefinition(): void
    {
        $this->cache->setDefinition($this->definition_alt);
        $this->assertSame(
            $this->definition_alt,
            $this->cache->getDefinition()
        );
    }

    public function testReturnCacheEntity(): void
    {
        $this->assertInstanceOf(
            CacheEntity::class,
            $this->cache->setDefinition($this->definition_alt)
        );
    }

    public function testThrowExceptionWhenNotAnArray(): void
    {
        $this->expectException(BadParameterException::class);
        $this->cache->setDefinition(42);
    }
}
