<?php

namespace PayPlug\tests\utilities\validators\ModuleValidator;

use PayPlug\src\utilities\validators\moduleValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group module_validator
 *
 * @dontrunTestsInSeparateProcesses
 */
class isFeatureTest extends TestCase
{
    protected $moduleValidator;
    protected $features = [];

    protected function setUp()
    {
        $this->moduleValidator = new moduleValidator();
        $this->features = [
            'features' => [
                'allowed_feature',
            ],
        ];
    }

    public function invalidFeaturesDataProvider()
    {
        yield [42];
        yield ['lorem Ipsum'];
        yield [false];
        yield [[]];
    }

    /**
     * @dataProvider invalidFeaturesDataProvider
     *
     * @param mixed $features
     */
    public function testWithInvalidFeaturesFormat($features)
    {
        $name = 'feature';
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid parameters given, $features must be an non empty array',
            ],
            $this->moduleValidator->isFeature($features, $name)
        );
    }

    public function invalidNameDataProvider()
    {
        yield [42];
        yield [['key' => 'value']];
        yield [false];
        yield [''];
    }

    /**
     * @dataProvider invalidNameDataProvider
     *
     * @param mixed $name
     */
    public function testWithInvalidNameFormat($name)
    {
        $features = [
            'key' => 'value',
        ];
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid parameters given, $name must be an non empty string',
            ],
            $this->moduleValidator->isFeature($features, $name)
        );
    }

    public function testWithUnknowFeatureName()
    {
        $name = 'allowed_feature';
        $this->assertSame(
            [
                'result' => true,
                'message' => '',
            ],
            $this->moduleValidator->isFeature($this->features, $name)
        );
    }

    public function testWithValidFeatureName()
    {
        $name = 'forbidden_feature';
        $this->assertSame(
            [
                'result' => false,
                'message' => 'The given $feature can\'t be use',
            ],
            $this->moduleValidator->isFeature($this->features, $name)
        );
    }
}
