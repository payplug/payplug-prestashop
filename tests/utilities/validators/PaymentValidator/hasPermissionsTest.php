<?php

use PayPlug\src\utilities\validators\paymentValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group payment_validator
 *
 * @runTestsInSeparateProcesses
 */
class hasPermissionsTest extends TestCase
{
    private $paymentValidator;

    protected function setUp()
    {
        $this->paymentValidator = new paymentValidator();
    }

    /**
     * @description invalid data provider
     *
     * @return Generator
     */
    public function invalidPermissionsDataProvider()
    {
        // invalid $permissions
        yield [[]];
        yield [false];
        yield [300];
        yield [null];
//        yield [['is_live' => ''], 1, ];
//        yield [['' => ''], 1];
//        yield [['' => ''], null];
//        yield [null, null];
    }

    /**
     * @description invalid feature data provider
     *
     * @return Generator
     */
    public function invalidFeatureDataProvider()
    {
        // invalid $permissions
        yield [[]];
        yield [false];
        yield [300];
        yield [null];
        yield [''];
    }

    public function validDataProvider()
    {
        yield [['is_live' => false], 'is_live'];
    }

    /**
     * @description  test with invalid Permissions data
     * @dataProvider invalidPermissionsDataProvider
     *
     * @param mixed $permissions
     */
    public function testWithInvalidPermissionsData($permissions)
    {
        $feature = 'is_live';
        $this->assertSame(['result' => false, 'message' => 'invalid argument, $permissions must be a non empty array.'], $this->paymentValidator->hasPermissions($permissions, $feature));
    }

    /**
     * @description  test with invalid Permissions data
     * @dataProvider invalidFeatureDataProvider
     *
     * @param mixed $feature
     */
    public function testWithInvalidFeaturesData($feature)
    {
        $permissions = ['is_live' => false];
        $this->assertSame(['result' => false, 'message' => 'invalid argument, $feature must be a non empty string.'], $this->paymentValidator->hasPermissions($permissions, $feature));
    }

    /**
     * @dataProvider validDataProvider
     *
     * @param $permissions
     * @param $feature
     */
    public function testHasPermissionsReturnWithValidData($permissions, $feature)
    {
        $this->assertSame($this->paymentValidator->hasPermissions($permissions, $feature)['result'], $permissions[$feature]);
    }
}
