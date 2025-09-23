<?php

namespace PayPlug\tests\utilities\validators\PaymentValidator;

use PayPlug\src\utilities\validators\paymentValidator;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\PaymentMock;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group payment_validator
 */
class canSaveCardTest extends TestCase
{
    use FormatDataProvider;

    public $validator;

    public function setUp()
    {
        $this->validator = new paymentValidator();
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $resource
     */
    public function testWhenGivenResourceIsInvalidObjectFormat($resource)
    {
        $this->assertFalse($this->validator->canSaveCard($resource));
    }

    public function testWhenGivenResourceIsInstallement()
    {
        $resource = PaymentMock::getInstallment();
        $this->assertFalse($this->validator->canSaveCard($resource));
    }

    public function testWhenGivenResourceIsInstallementSchedule()
    {
        $resource = PaymentMock::getInstallmentSchedule();
        $this->assertFalse($this->validator->canSaveCard($resource));
    }

    public function testWhenGivenResourceHasSaveCardProperty()
    {
        $resource = PaymentMock::getStandard(['save_card' => true]);
        $this->assertTrue($this->validator->canSaveCard($resource));
    }

    public function testWhenGivenResourceHasCompleteCardProperty()
    {
        $parameters = [
            'card' => [
                'last4' => '0001',
                'country' => 'FR',
                'exp_year' => 2030,
                'exp_month' => 9,
                'brand' => 'CB',
                'id' => 'card_3EOJHyQXNCG8gZ452cUA0y',
                'metadata' => null,
            ],
        ];
        $resource = PaymentMock::getStandard($parameters);
        $this->assertTrue($this->validator->canSaveCard($resource));
    }
}
