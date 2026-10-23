<?php

namespace PayPlug\tests\models\classes\ApmEligibility;

use PayPlug\src\models\classes\ApmEligibility;

/**
 * @group unit
 * @group class
 * @group apm_eligibility_class
 */
class getBrandNameTest extends BaseApmEligibility
{
    public function brandProvider()
    {
        yield ['satispay', 'Satispay'];

        yield ['bancontact', 'Bancontact'];

        yield ['ideal', 'iDEAL'];

        yield ['mybank', 'MyBank'];

        yield ['wero', 'Wero'];

        yield ['bizum', 'Bizum'];

        yield ['scalapay', 'Scalapay'];
    }

    /**
     * @dataProvider brandProvider
     *
     * @param string $name
     * @param string $brand
     */
    public function testPproMethodIsNamedByItsBrand($name, $brand)
    {
        $this->assertSame($brand, $this->class->getBrandName($name));
    }

    public function testEveryPproMethodHasABrandName()
    {
        foreach (ApmEligibility::PPRO_METHODS as $name) {
            $this->assertNotSame('', $this->class->getBrandName($name), $name);
        }
    }

    public function testNonPproMethodHasNoBrandName()
    {
        $this->assertSame('', $this->class->getBrandName('standard'));
        $this->assertSame('', $this->class->getBrandName(null));
    }
}
