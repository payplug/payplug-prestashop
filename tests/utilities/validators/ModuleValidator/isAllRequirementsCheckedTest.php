<?php

namespace PayPlug\tests\utilities\validators\ModuleValidator;

use PayPlug\src\utilities\validators\moduleValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group module_validator
 *
 * @runTestsInSeparateProcesses
 */
class isAllRequirementsCheckedTest extends TestCase
{
    protected $validator;

    protected function setUp()
    {
        $this->validator = new moduleValidator();
    }

    public function invalidDataProvider()
    {
        yield [42];
        yield ['lorem Ipsum'];
        yield [false];
        yield [[]];
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param mixed $report
     */
    public function testWithInvalidReportFormat($report)
    {
        $this->assertSame(
            [
                'result' => false,
                'code' => 'format',
                'message' => 'Invalid parameters given, $report must be an non empty array',
            ],
            $this->validator->isAllRequirementsChecked($report)
        );
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param mixed $php
     */
    public function testWithEmptyPhpArrayKey($php)
    {
        $report = [
            'php' => $php,
            'curl' => [
                'installed' => true,
            ],
            'openssl' => [
                'up2date' => true,
                'installed' => true,
            ],
        ];
        $this->assertSame(
            [
                'result' => false,
                'code' => 'php_format',
                'message' => 'Invalid argument given, $report[php] must be a non empty array',
            ],
            $this->validator->isAllRequirementsChecked($report)
        );
    }

    public function testWithMissingArrayKeyForPHPUp2date()
    {
        $report = [
            'php' => [
                'key' => 'value',
            ],
            'curl' => [
                'installed' => true,
            ],
            'openssl' => [
                'up2date' => true,
                'installed' => true,
            ],
        ];
        $this->assertSame(
            [
                'result' => false,
                'code' => 'php_format',
                'message' => 'Missing array key: $report[php][up2date]',
            ],
            $this->validator->isAllRequirementsChecked($report)
        );
    }

    public function testWhenArrayKeyForPHPUp2dateReturnFalse()
    {
        $report = [
            'php' => [
                'up2date' => false,
            ],
            'curl' => [
                'installed' => true,
            ],
            'openssl' => [
                'up2date' => true,
                'installed' => true,
            ],
        ];
        $this->assertSame(
            [
                'result' => false,
                'code' => 'php_requirements',
                'message' => 'Wrong requirement: The minimum requirement for PHP is not respected',
            ],
            $this->validator->isAllRequirementsChecked($report)
        );
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param mixed $curl
     */
    public function testWithEmptyCurlArrayKey($curl)
    {
        $report = [
            'php' => [
                'up2date' => true,
            ],
            'curl' => $curl,
            'openssl' => [
                'up2date' => true,
                'installed' => true,
            ],
        ];
        $this->assertSame(
            [
                'result' => false,
                'code' => 'curl_format',
                'message' => 'Invalid argument given, $report[curl] must be a non empty array',
            ],
            $this->validator->isAllRequirementsChecked($report)
        );
    }

    public function testWithMissingArrayCurlForPHPUp2date()
    {
        $report = [
            'php' => [
                'up2date' => true,
            ],
            'curl' => [
                'key' => 'value',
            ],
            'openssl' => [
                'up2date' => true,
                'installed' => true,
            ],
        ];
        $this->assertSame(
            [
                'result' => false,
                'code' => 'curl_format',
                'message' => 'Missing array key: $report[curl][installed]',
            ],
            $this->validator->isAllRequirementsChecked($report)
        );
    }

    public function testWhenArrayKeyForCurlUp2dateReturnFalse()
    {
        $report = [
            'php' => [
                'up2date' => true,
            ],
            'curl' => [
                'installed' => false,
            ],
            'openssl' => [
                'up2date' => true,
                'installed' => true,
            ],
        ];
        $this->assertSame(
            [
                'result' => false,
                'code' => 'curl_requirements',
                'message' => 'Wrong requirement: The minimum requirement for Curl is not respected',
            ],
            $this->validator->isAllRequirementsChecked($report)
        );
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param mixed $openssl
     */
    public function testWithEmptyOpenSSLArrayKey($openssl)
    {
        $report = [
            'php' => [
                'up2date' => true,
            ],
            'curl' => [
                'installed' => true,
            ],
            'openssl' => $openssl,
        ];
        $this->assertSame(
            [
                'result' => false,
                'code' => 'openssl_format',
                'message' => 'Invalid argument given, $report[openssl] must be a non empty array',
            ],
            $this->validator->isAllRequirementsChecked($report)
        );
    }

    public function testWithMissingArrayOpenSSLForPHPUp2date()
    {
        $report = [
            'php' => [
                'up2date' => true,
            ],
            'curl' => [
                'installed' => true,
            ],
            'openssl' => [
                'key' => 'value',
            ],
        ];
        $this->assertSame(
            [
                'result' => false,
                'code' => 'openssl_format',
                'message' => 'Missing array key: $report[openssl][installed] and/or $report[openssl][up2date]',
            ],
            $this->validator->isAllRequirementsChecked($report)
        );
    }

    public function testWhenArrayKeyForOpenSSLUp2dateReturnFalse()
    {
        $report = [
            'php' => [
                'up2date' => true,
            ],
            'curl' => [
                'installed' => true,
            ],
            'openssl' => [
                'up2date' => false,
                'installed' => true,
            ],
        ];
        $this->assertSame(
            [
                'result' => false,
                'code' => 'openssl_requirements',
                'message' => 'Wrong requirement: The minimum requirement for OpenSSL is not respected',
            ],
            $this->validator->isAllRequirementsChecked($report)
        );
    }

    public function testWhenArrayKeyForOpenSSLInstalledReturnFalse()
    {
        $report = [
            'php' => [
                'up2date' => true,
            ],
            'curl' => [
                'installed' => true,
            ],
            'openssl' => [
                'up2date' => true,
                'installed' => false,
            ],
        ];
        $this->assertSame(
            [
                'result' => false,
                'code' => 'openssl_requirements',
                'message' => 'Wrong requirement: The minimum requirement for OpenSSL is not respected',
            ],
            $this->validator->isAllRequirementsChecked($report)
        );
    }

    public function testWhenAllRequirementIsValidated()
    {
        $report = [
            'php' => [
                'up2date' => true,
            ],
            'curl' => [
                'installed' => true,
            ],
            'openssl' => [
                'up2date' => true,
                'installed' => true,
            ],
        ];
        $this->assertSame(
            [
                'result' => true,
                'message' => '',
            ],
            $this->validator->isAllRequirementsChecked($report)
        );
    }
}
