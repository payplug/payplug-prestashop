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

namespace PayPlug\tests\repositories\InstallRepository;

/**
 * @group unit
 * @group repository
 * @group install
 * @group install_repository
 *
 * @runTestsInSeparateProcesses
 *
 * @internal
 * @coversNothing
 */
final class InstallTest extends BaseInstallRepository
{
    public function setUp()
    {
        parent::setUp();

        $this->repo
            ->shouldReceive('setInstallError')
            ->andReturnUsing(function ($string) {
                return $string;
            })
        ;
    }

    public function testWithInvalidPHPRequirement()
    {
        $this->dependencies->configClass
            ->shouldReceive([
                'checkRequirements' => [
                    'php' => [
                        'version' => 0,
                        'installed' => true,
                        'up2date' => false,
                    ],
                    'curl' => [
                        'version' => 0,
                        'installed' => false,
                        'up2date' => false,
                    ],
                    'openssl' => [
                        'version' => 0,
                        'installed' => false,
                        'up2date' => false,
                    ],
                ],
            ])
        ;

        $this->assertSame(
            'Install failed: PHP Requirement.',
            $this->repo->install()
        );
    }

    public function testWithInvalidCurlRequirement()
    {
        $this->dependencies->configClass
            ->shouldReceive([
                'checkRequirements' => [
                    'php' => [
                        'up2date' => true,
                    ],
                    'curl' => [
                        'up2date' => false,
                    ],
                    'openssl' => [
                        'up2date' => false,
                    ],
                ],
            ])
        ;

        $this->assertSame(
            'Install failed: cURL Requirement.',
            $this->repo->install()
        );
    }

    public function testWithInvalidOpenSSLRequirement()
    {
        $this->dependencies->configClass
            ->shouldReceive([
                'checkRequirements' => [
                    'php' => [
                        'up2date' => true,
                    ],
                    'curl' => [
                        'up2date' => true,
                    ],
                    'openssl' => [
                        'up2date' => false,
                    ],
                ],
            ])
        ;

        $this->assertSame(
            'Install failed: OpenSSL Requirement.',
            $this->repo->install()
        );
    }

    public function testWithInvalidConfigInstall()
    {
        $this->dependencies->configClass
            ->shouldReceive([
                'checkRequirements' => [
                    'php' => [
                        'up2date' => true,
                    ],
                    'curl' => [
                        'up2date' => true,
                    ],
                    'openssl' => [
                        'up2date' => true,
                    ],
                ],
            ])
        ;

        $this->repo
            ->shouldReceive([
                'setConfig' => false,
            ])
        ;

        $this->assertSame(
            'Install failed:setConfig()',
            $this->repo->install()
        );
    }

    public function testWithInvalidSqlInstall()
    {
        $this->dependencies->configClass
            ->shouldReceive([
                'checkRequirements' => [
                    'php' => [
                        'up2date' => true,
                    ],
                    'curl' => [
                        'up2date' => true,
                    ],
                    'openssl' => [
                        'up2date' => true,
                    ],
                ],
            ])
        ;

        $this->repo
            ->shouldReceive([
                'setConfig' => true,
            ])
        ;

        $this->sql
            ->shouldReceive([
                'installSQL' => false,
            ])
        ;

        $this->assertSame(
            'Install failed: Install SQL tables.',
            $this->repo->install()
        );
    }

    public function testWithInvalidOrderStateInstall()
    {
        $this->dependencies->configClass
            ->shouldReceive([
                'checkRequirements' => [
                    'php' => [
                        'up2date' => true,
                    ],
                    'curl' => [
                        'up2date' => true,
                    ],
                    'openssl' => [
                        'up2date' => true,
                    ],
                ],
            ])
        ;

        $this->repo
            ->shouldReceive([
                'setConfig' => true,
                'createOrderStates' => false,
            ])
        ;

        $this->sql
            ->shouldReceive([
                'installSQL' => true,
            ])
        ;

        $this->assertSame(
            'Install failed: Create order states.',
            $this->repo->install()
        );
    }

    public function testWithInvalidOrderStateTypeInstall()
    {
        $this->dependencies->configClass
            ->shouldReceive([
                'checkRequirements' => [
                    'php' => [
                        'up2date' => true,
                    ],
                    'curl' => [
                        'up2date' => true,
                    ],
                    'openssl' => [
                        'up2date' => true,
                    ],
                ],
            ])
        ;

        $this->repo
            ->shouldReceive([
                'setConfig' => true,
                'createOrderStates' => true,
                'createOrderStatesType' => false,
            ])
        ;

        $this->sql
            ->shouldReceive([
                'installSQL' => true,
            ])
        ;

        $this->assertSame(
            'Install failed: Create order states type.',
            $this->repo->install()
        );
    }

    public function testWithInvalidTableInstall()
    {
        $this->dependencies->configClass
            ->shouldReceive([
                'checkRequirements' => [
                    'php' => [
                        'up2date' => true,
                    ],
                    'curl' => [
                        'up2date' => true,
                    ],
                    'openssl' => [
                        'up2date' => true,
                    ],
                ],
            ])
        ;

        $this->repo
            ->shouldReceive([
                'setConfig' => true,
                'createOrderStates' => true,
                'createOrderStatesType' => true,
            ])
        ;

        $this->sql
            ->shouldReceive([
                'installSQL' => true,
            ])
        ;

        $adapter = \Mockery::mock();
        $this->dependencies
            ->shouldReceive('loadAdapterPresta')
            ->andReturn($adapter)
        ;
        $adapter
            ->shouldReceive([
                'installTab' => false,
            ])
        ;

        $this->assertSame(
            'Install failed: Install Tab',
            $this->repo->install()
        );
    }

    public function testValidInstallation()
    {
        $this->dependencies->configClass
            ->shouldReceive([
                'checkRequirements' => [
                    'php' => [
                        'up2date' => true,
                    ],
                    'curl' => [
                        'up2date' => true,
                    ],
                    'openssl' => [
                        'up2date' => true,
                    ],
                ],
            ])
        ;

        $this->repo
            ->shouldReceive([
                'setConfig' => true,
                'createOrderStates' => true,
                'createOrderStatesType' => true,
            ])
        ;

        $this->sql
            ->shouldReceive([
                'installSQL' => true,
            ])
        ;

        $adapter = \Mockery::mock();
        $this->dependencies
            ->shouldReceive('loadAdapterPresta')
            ->andReturn($adapter)
        ;
        $adapter
            ->shouldReceive([
                'installTab' => true,
            ])
        ;

        $this->assertSame(
            true,
            $this->repo->install()
        );
    }
}
