<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group class
 * @group configuration_class
 */
class apmConfigurationKeysTest extends BaseConfiguration
{
    public function testApmCustomSettingsDefaultsToAnEmptyJsonObject()
    {
        $this->assertSame('{}', $this->class->getDefault('apm_custom_settings'));
        $this->assertSame('PAYPLUG_APM_CUSTOM_SETTINGS', $this->class->getName('apm_custom_settings'));
    }
}
