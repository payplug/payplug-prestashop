<?php

namespace PayPlug\tests\models\classes\ApiRest;

use PayPlug\src\utilities\validators\moduleValidator;

/**
 * @group unit
 * @group classes
 * @group apirest_classes
 *
 * @runTestsInSeparateProcesses
 */
class getRequirementsSectionTest extends BaseApiRest
{
    protected $moduleValidator;

    public function setUp()
    {
        parent::setUp();
        $this->moduleValidator = new ModuleValidator();
    }

    /**
     * @description  test the requirements status if there is a problem with curl
     */
    public function testIfCurlRequirementIsUnchecked()
    {
        $report = [
            'php' => [
                'version' => 0,
                'installed' => true,
                'up2date' => true,
            ],
            'curl' => [
                'version' => '7.74.0',
                'installed' => false,
                'up2date' => true,
            ],
            'openssl' => [
                'version' => 269488319,
                'installed' => true,
                'up2date' => true,
            ],
        ];
        $this->configuration_helper->shouldReceive([
            'getRequirements' => $report,
        ]);
        $response = $this->classe->getRequirementsSection();

        foreach ($response['requirements'] as $requirement) {
            if ('payplug.getRequirementsTranslations.requirementsCurlText' == $requirement['text']) {
                $this->assertSame(false, $requirement['status']);
            }
        }
    }

    /**
     * @description test requirements status if curl is well installed
     */
    public function testIfCurlRequirementIseChecked()
    {
        $report = [
            'php' => [
                'version' => 0,
                'installed' => true,
                'up2date' => true,
            ],
            'curl' => [
                'version' => '7.74.0',
                'installed' => true,
                'up2date' => true,
            ],
            'openssl' => [
                'version' => 269488319,
                'installed' => true,
                'up2date' => true,
            ],
        ];
        $this->configuration_helper->shouldReceive([
            'getRequirements' => $report,
        ]);
        $response = $this->classe->getRequirementsSection();

        foreach ($response['requirements'] as $requirement) {
            if ('payplug.getRequirementsTranslations.requirementsCurlText' == $requirement['text']) {
                $this->assertSame(true, $requirement['status']);
            }
        }
    }

    /**
     * @description  test if when  php requirement is not satisfied
     */
    public function testIfPhpRequirementIsUnchecked()
    {
        $report = [
            'php' => [
                'version' => 0,
                'installed' => true,
                'up2date' => false,
            ],
            'curl' => [
                'version' => '7.74.0',
                'installed' => true,
                'up2date' => true,
            ],
            'openssl' => [
                'version' => 269488319,
                'installed' => true,
                'up2date' => true,
            ],
        ];
        $this->configuration_helper->shouldReceive([
            'getRequirements' => $report,
        ]);
        $response = $this->classe->getRequirementsSection();

        foreach ($response['requirements'] as $requirement) {
            if ('payplug.getRequirementsTranslations.requirementsPhpText' == $requirement['text']) {
                $this->assertSame(false, $requirement['status']);
            }
        }
    }

    /**
     * @description test when php requirement is good
     */
    public function testIfPhpRequirementIschecked()
    {
        $report = [
            'php' => [
                'version' => 0,
                'installed' => true,
                'up2date' => true,
            ],
            'curl' => [
                'version' => '7.74.0',
                'installed' => true,
                'up2date' => true,
            ],
            'openssl' => [
                'version' => 269488319,
                'installed' => true,
                'up2date' => true,
            ],
        ];
        $this->configuration_helper->shouldReceive([
            'getRequirements' => $report,
        ]);
        $response = $this->classe->getRequirementsSection();

        foreach ($response['requirements'] as $requirement) {
            if ('payplug.getRequirementsTranslations.requirementsPhpText' == $requirement['text']) {
                $this->assertSame(true, $requirement['status']);
            }
        }
    }

    /**
     * @description  test when openSsl requirement is not satisied
     */
    public function testIfOpenSslRequirementIsUnchecked()
    {
        $report = [
            'php' => [
                'version' => 0,
                'installed' => true,
                'up2date' => true,
            ],
            'curl' => [
                'version' => '7.74.0',
                'installed' => true,
                'up2date' => true,
            ],
            'openssl' => [
                'version' => 269488319,
                'installed' => false,
                'up2date' => true,
            ],
        ];
        $this->configuration_helper->shouldReceive([
            'getRequirements' => $report,
        ]);
        $response = $this->classe->getRequirementsSection();

        foreach ($response['requirements'] as $requirement) {
            if ('payplug.getRequirementsTranslations.requirementsOpensslText' == $requirement['text']) {
                $this->assertSame(false, $requirement['status']);
            }
        }
    }

    /**
     * @description test when openSsl requirement is good
     */
    public function testIfOpenSslRequirementIsChecked()
    {
        $report = [
            'php' => [
                'version' => 0,
                'installed' => true,
                'up2date' => true,
            ],
            'curl' => [
                'version' => '7.74.0',
                'installed' => true,
                'up2date' => true,
            ],
            'openssl' => [
                'version' => 269488319,
                'installed' => true,
                'up2date' => true,
            ],
        ];
        $this->configuration_helper->shouldReceive([
            'getRequirements' => $report,
        ]);
        $response = $this->classe->getRequirementsSection();

        foreach ($response['requirements'] as $requirement) {
            if ('payplug.getRequirementsTranslations.requirementsOpensslText' == $requirement['text']) {
                $this->assertSame(true, $requirement['status']);
            }
        }
    }
}
