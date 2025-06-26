<?php

namespace PayPlug\tests\utilities\validators\RegexValidator;

/**
 * @group unit
 * @group validator
 * @group regex_validator
 */
class isApiUrlTest extends BaseRegexValidator
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $api_url
     */
    public function testWhenGivenApiUrlIsntValidStringFormat($api_url)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid parameter given, $api_url must be a non empty string.',
            ],
            $this->validator->isApiUrl($api_url)
        );
    }

    public function testWithValidNonMobileDeviceUserAgent()
    {
        $api_url = 'invalid api url';
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Given url isn\'t valid api url.',
            ],
            $this->validator->isApiUrl($api_url)
        );
    }

    public function testWithValidMobileDeviceUserAgent()
    {
        $api_url = 'https://api.payplug.com';
        $this->assertSame(
            [
                'result' => true,
                'message' => 'Given url is valid api url.',
            ],
            $this->validator->isApiUrl($api_url)
        );
    }
}
