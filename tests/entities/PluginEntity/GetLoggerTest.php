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

declare(strict_types=1);

use PayPlug\src\entities\LoggerEntity;
use PayPlug\src\entities\PluginEntity;
use PHPUnit\Framework\TestCase;

final class GetLoggerTest extends TestCase
{
    public function test_return_a_logger_entity(): void
    {
        $plugin = new PluginEntity();
        $logger = new LoggerEntity();
        $plugin->setLogger($logger);
        $this->assertInstanceOf(
            LoggerEntity::class,
            $plugin->getLogger()
        );
    }
}