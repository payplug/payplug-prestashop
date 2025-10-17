<?php

namespace PayPlug\tests\models\classes\Translation;

/**
 * @group unit
 * @group class
 * @group translation_class
 *
 * @runTestsInSeparateProcesses
 */
class getTranslationsTest extends BaseTranslation
{
    public function setUp()
    {
        parent::setUp();
        $this->class->shouldReceive('l')
            ->andReturnUsing(function ($str) {
                return $str;
            });
    }

    public function testIfCardTranslations()
    {
        $this->assertTrue(
            is_array($this->class->getCardTranslations())
            && !empty($this->class->getCardTranslations())
        );
    }

    public function testIfFooterTranslations()
    {
        $this->assertTrue(
            is_array($this->class->getFooterTranslations())
            && !empty($this->class->getFooterTranslations())
        );
    }

    public function testIfFrontIntegratedPaymentTranslations()
    {
        $this->assertTrue(
            is_array($this->class->getFrontIntegratedPaymentTranslations())
            && !empty($this->class->getFrontIntegratedPaymentTranslations())
        );
    }

    public function testIfHeaderTranslations()
    {
        $this->assertTrue(
            is_array($this->class->getHeaderTranslations())
            && !empty($this->class->getHeaderTranslations())
        );
    }

    public function testIfLoggedTranslations()
    {
        $this->assertTrue(
            is_array($this->class->getLoggedTranslations())
            && !empty($this->class->getLoggedTranslations())
        );
    }

    public function testIfLoginTranslations()
    {
        $this->assertTrue(
            is_array($this->class->getLoginTranslations())
            && !empty($this->class->getLoginTranslations())
        );
    }

    public function testIfModalTranslations()
    {
        $this->assertTrue(
            is_array($this->class->getModalTranslations())
            && !empty($this->class->getModalTranslations())
        );
    }

    public function testIfOrderTranslations()
    {
        $this->assertTrue(
            is_array($this->class->getOrderTranslations())
            && !empty($this->class->getOrderTranslations())
        );
    }

    public function testIfOrderStateActionRenderTranslations()
    {
        $this->assertTrue(
            is_array($this->class->getOrderStateActionRenderTranslations())
            && !empty($this->class->getOrderStateActionRenderTranslations())
        );
    }

    public function testIfPaylaterTranslations()
    {
        $this->assertTrue(
            is_array($this->class->getPaylaterTranslations())
            && !empty($this->class->getPaylaterTranslations())
        );
    }

    public function testIfPaymentMethodsTranslations()
    {
        $this->assertTrue(
            is_array($this->class->getPaymentMethodsTranslations())
            && !empty($this->class->getPaymentMethodsTranslations())
        );
    }

    public function testIfRequirementsTranslations()
    {
        $this->assertTrue(
            is_array($this->class->getRequirementsTranslations())
            && !empty($this->class->getRequirementsTranslations())
        );
    }

    public function testIfSubscribeTranslations()
    {
        $this->assertTrue(
            is_array($this->class->getSubscribeTranslations())
            && !empty($this->class->getSubscribeTranslations())
        );
    }
}
