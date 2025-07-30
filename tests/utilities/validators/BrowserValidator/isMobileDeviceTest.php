<?php

use PayPlug\src\utilities\validators\browserValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group module_validator
 */
class isMobileDeviceTest extends TestCase
{
    protected $validator;
    private $browserValidator;

    public function setUp()
    {
        $this->browserValidator = new BrowserValidator();
    }

    /**
     * @description  invalid $userAgent data provider
     *
     * @return Generator
     */
    public function invalidUserAgentDataProvider()
    {
        yield [[]];

        yield [''];

        yield [null];

        yield [['key' => 'value']];

        yield [300];
    }

    /**
     * @description a non mobiledevice $userAgent provider
     *
     * @return Generator
     */
    public function nonMobileDeviceUserAgentData()
    {
        yield ['Mozilla/5.0 (X11; Linux x86_64; rv:102.0) Gecko/201888181 Firefox/102.0'];
    }

    /**
     * @description mobile device $userAgent data provider
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
     * @description test with invalid $userAgent format
     * @dataProvider invalidUserAgentDataProvider
     *
     * @param mixed $userAgent
     */
    public function testWithInvalidArgumentsFormat($userAgent)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid parameter given, $useragent must be a non empty string.',
            ],
            $this->browserValidator->isMobileDevice($userAgent)
        );
    }

    /**
     * @description test with valid $userAgent format: non mobile Device case
     * @dataProvider nonMobileDeviceUserAgentData
     *
     * @param mixed $userAgent
     */
    public function testWithValidNonMobileDeviceUserAgent($userAgent)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Current device is not a mobile device.',
            ],
            $this->browserValidator->isMobileDevice($userAgent)
        );
    }

    /**
     * @description test with valid $userAgent format: mobile Device case
     * @dataProvider mobileDeviceUserAgentData
     *
     * @param mixed $userAgent
     */
    public function testWithValidMobileDeviceUserAgent($userAgent)
    {
        $this->assertSame(
            [
                'result' => true,
                'message' => 'Current device is a mobile device.',
            ],
            $this->browserValidator->isMobileDevice($userAgent)
        );
    }
}
