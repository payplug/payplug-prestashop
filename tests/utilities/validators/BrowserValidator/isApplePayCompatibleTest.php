<?php

use PayPlug\src\utilities\validators\browserValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group module_validator
 *
 * @dontrunTestsInSeparateProcesses
 */
class isApplePayCompatibleTest extends TestCase
{
    protected $validator;
    private $browserValidator;

    protected function setUp()
    {
        $this->browserValidator = new BrowserValidator();
    }

    /**
     * @description  invalid $browser format  data provider
     *
     * @return Generator
     */
    public function invalidBrowserDataProvider()
    {
        yield [[]];
        yield [''];
        yield [null];
        yield [['key' => 'value']];
        yield [300];
    }

    /**
     * @description  non compatible apple pay browsers provider
     *
     * @return Generator
     */
    public function nonSafariBrowserDataProvider()
    {
        yield ['Opera'];
        yield ['Edge'];
        yield ['Chrome'];
        yield ['Firefox'];
        yield ['MSIE'];
        yield ['Trident'];
        yield ['Brave'];
        yield ['Vivaldi'];
    }

    /**
     * @description test with invalid $browser format
     * @dataProvider invalidBrowserDataProvider
     *
     * @param mixed $browser
     */
    public function testWithBrowserFormat($browser)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid parameter given, $browser must be a non empty string.',
            ],
            $this->browserValidator->isApplePayCompatible($browser)
        );
    }

    /**
     * @description  test when browser is compatible with ApplePay
     */
    public function testWhenBrowserIsSafari()
    {
        $this->assertSame(
            [
                'result' => true,
                'message' => 'This browser is applepay compatible.',
            ],
            $this->browserValidator->isApplePayCompatible('Safari')
        );
    }

    /**
     * @description  test when browser is not compatible with ApplePay
     * @dataProvider nonSafariBrowserDataProvider
     *
     * @param $browser
     */
    public function testWhenBrowserIsntSafari($browser)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'This browser is not applepay compatible.',
            ],
            $this->browserValidator->isApplePayCompatible($browser)
        );
    }
}
