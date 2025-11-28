<?php

namespace PayPlug\tests\utilities\services\PhoneNumber;

/**
 * @group unit
 * @group service
 * @group phone_number_service
 */
class formatPhoneNumberTest extends BasePhoneNumber
{
    public $phone_number;
    public $iso_code;

    public function setUp()
    {
        parent::setUp();

        $this->phone_number = '06 70 00 00 00';
        $this->iso_code = 'fr';
    }

    /**
     * @dataProvider invalidPhoneFormatDataProvider
     *
     * @param mixed $phone_number
     */
    public function testWhenGivenPhoneNumberIsntValidString($phone_number)
    {
        $this->assertSame(
            '',
            $this->service->formatPhoneNumber($phone_number, $this->iso_code)
        );
        $this->assertSame(
            [
                'PhoneNumber::formatPhoneNumber - Invalid argument, given phone_number id must be a valid phone number.',
            ],
            $this->logs
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $iso_code
     */
    public function testWhenGivenIsoCodeIsntValidString($iso_code)
    {
        $this->assertSame(
            '',
            $this->service->formatPhoneNumber($this->phone_number, $iso_code)
        );
        $this->assertSame(
            [
                'PhoneNumber::formatPhoneNumber - Invalid argument, given iso_code must be non empty string.',
            ],
            $this->logs
        );
    }

    public function testWhenParseMethodThrowAnException()
    {
        $this->phone_number_util
            ->shouldReceive('getInstance')
            ->andReturn($this->phone_number_util);
        $this->phone_number_util
            ->shouldReceive('parse')
            ->andThrow(new \Exception('An error occured during the usage of method: parse().', 500));

        $this->assertSame(
            '',
            $this->service->formatPhoneNumber($this->phone_number, $this->iso_code)
        );
        $this->assertSame(
            [
                'PhoneNumber::formatPhoneNumber - Exception thrown: An error occured during the usage of method: parse().',
            ],
            $this->logs
        );
    }

    public function testWhenIsValidNumberMethodThrowAnException()
    {
        $this->phone_number_util
            ->shouldReceive([
                'getInstance' => $this->phone_number_util,
                'parse' => $this->phone_number_util,
            ]);
        $this->phone_number_util
            ->shouldReceive('isValidNumber')
            ->andThrow(new \Exception('An error occured during the usage of method: IsValidNumber().', 500));

        $this->assertSame(
            '',
            $this->service->formatPhoneNumber($this->phone_number, $this->iso_code)
        );
        $this->assertSame(
            [
                'PhoneNumber::formatPhoneNumber - Exception thrown: An error occured during the usage of method: IsValidNumber().',
            ],
            $this->logs
        );
    }

    public function testWhenFormatMethodThrowAnException()
    {
        $this->phone_number_util
            ->shouldReceive([
                'getInstance' => $this->phone_number_util,
                'parse' => $this->phone_number_util,
                'isValidNumber' => $this->phone_number_util,
            ]);
        $this->phone_number_util
            ->shouldReceive('format')
            ->andThrow(new \Exception('An error occured during the usage of method: Format().', 500));

        $this->assertSame(
            '',
            $this->service->formatPhoneNumber($this->phone_number, $this->iso_code)
        );
        $this->assertSame(
            [
                'PhoneNumber::formatPhoneNumber - Exception thrown: An error occured during the usage of method: Format().',
            ],
            $this->logs
        );
    }

    public function testWhenFormatedNumberIsNotValid()
    {
        $formated_phone = 'invalid phone number';
        $this->phone_number_util
            ->shouldReceive([
                'getInstance' => $this->phone_number_util,
                'parse' => $this->phone_number_util,
                'isValidNumber' => $this->phone_number_util,
                'format' => $formated_phone,
            ]);

        $this->assertSame(
            $formated_phone,
            $this->service->formatPhoneNumber($this->phone_number, $this->iso_code)
        );
    }

    public function testWhenFormatedPhoneNumberIsReturned()
    {
        $formated_phone = '+33 6 70 00 00 00';
        $this->phone_number_util
            ->shouldReceive([
                'getInstance' => $this->phone_number_util,
                'parse' => $this->phone_number_util,
                'isValidNumber' => $this->phone_number_util,
                'format' => '+33 6 70 00 00 00',
            ]);

        $this->assertSame(
            $formated_phone,
            $this->service->formatPhoneNumber($this->phone_number, $this->iso_code)
        );
    }
}
