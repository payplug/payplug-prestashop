<?php

namespace PayPlug\tests\utilities\validators\RegexValidator;

/**
 * @group unit
 * @group validator
 * @group regex_validator
 *
 * @runTestsInSeparateProcesses
 */
class isMobileDeviceTest extends BaseRegexValidator
{
    /**
     * @description a non mobiledevice $user_agent provider
     *
     * @return Generator
     */
    public function nonMobileDeviceUserAgentData()
    {
        yield ['Mozilla/5.0 (X11; Linux x86_64; rv:102.0) Gecko/201888181 Firefox/102.0'];
    }

    /**
     * @description mobile device $user_agent data provider
     *
     * @return Generator
     */
    public function mobileDeviceUserAgentData()
    {
        yield ['Mozilla/5.0 (Linux; Android 7.0; SM-G930V Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.125 Mobile Safari/537.36'];

        yield ['Mozilla/5.0 (Android 7.0; Mobile; rv:54.0) Gecko/54.0 Firefox/54.0'];

        yield ['Mozilla/5.0 (Linux; Android 6.0.1; SM-G920V Build/MMB29K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.98 Mobile Safari/537.36'];

        yield ['Mozilla/5.0 (BB10; Kbd) AppleWebKit/537.35+ (KHTML, like Gecko) Version/10.3.3.2205 Mobile Safari/537.35+'];
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $user_agent
     */
    public function testWhenGivenUserAgentIsntValidStringFormat($user_agent)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid parameter given, $user_agent must be a non empty string.',
            ],
            $this->validator->isMobileDevice($user_agent)
        );
    }

    /**
     * @dataProvider nonMobileDeviceUserAgentData
     *
     * @param mixed $user_agent
     */
    public function testWhenGivenUserAgentIsntAMobileDevice($user_agent)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Current device is not a mobile device.',
            ],
            $this->validator->isMobileDevice($user_agent)
        );
    }

    /**
     * @dataProvider mobileDeviceUserAgentData
     *
     * @param mixed $user_agent
     */
    public function testWhenGivenUserAgentIsAMobileDevice($user_agent)
    {
        $this->assertSame(
            [
                'result' => true,
                'message' => 'Current device is a mobile device.',
            ],
            $this->validator->isMobileDevice($user_agent)
        );
    }
}
