<?php

namespace PayPlug\tests\unified\contract\UnifiedHttpClient;

/**
 * @group unit
 * @group unified
 */
class isSslVerificationEnabledTest extends BaseUnifiedHttpClient
{
    public function testSslVerificationIsEnabledByDefaultWhenTheEnvVarIsUnset()
    {
        unset($_ENV['SSL_VERIFYPEER']);

        $this->assertTrue($this->client->isSslVerificationEnabled('SSL_VERIFYPEER'));
    }

    public function testSslVerificationIsDisabledOnlyByAnExplicitZero()
    {
        $_ENV['SSL_VERIFYPEER'] = '0';

        $this->assertFalse($this->client->isSslVerificationEnabled('SSL_VERIFYPEER'));
    }

    /**
     * @dataProvider nonDisablingValueDataProvider
     *
     * @param mixed $value
     */
    public function testSslVerificationStaysEnabledForAnyValueOtherThanExplicitZero($value)
    {
        $_ENV['SSL_VERIFYHOST'] = $value;

        $this->assertTrue($this->client->isSslVerificationEnabled('SSL_VERIFYHOST'));
    }

    public function nonDisablingValueDataProvider()
    {
        yield ['1'];

        yield ['true'];

        yield [''];
    }
}
