<?php

namespace PayPlug\tests\models\classes\ApiRest;

/**
 * @group unit
 * @group class
 * @group apirest_class
 */
class getRequirementsSectionTest extends BaseApiRest
{
    public $alias;
    public $report;

    public function setUp()
    {
        parent::setUp();
        $this->alias = [
            'openssl' => 'payplug.getRequirementsTranslations.requirementsOpensslText',
            'php' => 'payplug.getRequirementsTranslations.requirementsPhpText',
            'curl' => 'payplug.getRequirementsTranslations.requirementsCurlText',
        ];
        $this->report = [
            'php' => [
                'up2date' => true,
            ],
            'curl' => [
                'installed' => true,
            ],
            'openssl' => [
                'installed' => true,
                'up2date' => true,
            ],
        ];
    }

    public function testWhenPHPIsNotUpdated()
    {
        $report = $this->report;
        $report['php']['up2date'] = false;
        $this->configuration_helper->shouldReceive([
            'getRequirements' => $report,
        ]);
        $requirements = $this->class->getRequirementsSection()['requirements'];
        foreach ($requirements as $requirement) {
            if ($this->alias['php'] == $requirement['text']) {
                $this->assertSame(false, $requirement['status']);
            }
        }
    }

    public function testWhenCurlIsNotInstalled()
    {
        $report = $this->report;
        $report['curl']['installed'] = false;
        $this->configuration_helper->shouldReceive([
            'getRequirements' => $report,
        ]);
        $requirements = $this->class->getRequirementsSection()['requirements'];
        foreach ($requirements as $requirement) {
            if ($this->alias['curl'] == $requirement['text']) {
                $this->assertSame(false, $requirement['status']);
            }
        }
    }

    public function testWhenOpenSSLIsNotInstalled()
    {
        $report = $this->report;
        $report['openssl']['installed'] = false;
        $this->configuration_helper->shouldReceive([
            'getRequirements' => $report,
        ]);
        $requirements = $this->class->getRequirementsSection()['requirements'];
        foreach ($requirements as $requirement) {
            if ($this->alias['openssl'] == $requirement['text']) {
                $this->assertSame(false, $requirement['status']);
            }
        }
    }

    public function testWhenOpenSSLIsNotUpdated()
    {
        $report = $this->report;
        $report['openssl']['up2date'] = false;
        $this->configuration_helper->shouldReceive([
            'getRequirements' => $report,
        ]);
        $requirements = $this->class->getRequirementsSection()['requirements'];
        foreach ($requirements as $requirement) {
            if ($this->alias['openssl'] == $requirement['text']) {
                $this->assertSame(false, $requirement['status']);
            }
        }
    }

    public function testWhenRequirementAreValid()
    {
        $this->configuration_helper->shouldReceive([
            'getRequirements' => $this->report,
        ]);
        $requirements = $this->class->getRequirementsSection()['requirements'];
        $expected = [
            [
                'status' => true,
                'text' => 'payplug.getRequirementsTranslations.requirementsOpensslText',
            ],
            [
                'status' => true,
                'text' => 'payplug.getRequirementsTranslations.requirementsPhpText',
            ],
            [
                'status' => true,
                'text' => 'payplug.getRequirementsTranslations.requirementsCurlText',
            ],
        ];
        $this->assertSame($expected, $requirements);
    }
}
