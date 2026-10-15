<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_class
 */
class processPaymentErrorTest extends BasePaymentMethod
{
    public function testRefusalReasonReturnedByTheApiIsLogged()
    {
        $result = $this->class->processPaymentError(400, $this->default_payment_tab, [
            'result' => false,
            'code' => 400,
            'message' => 'Bad request.',
            'details' => [
                'object' => 'error',
                'error_type' => 'invalid_request_error',
                'message' => 'The phone number format is invalid.',
                'details' => [
                    'billing.landline_phone_number' => 'invalid',
                ],
            ],
        ]);

        $this->assertSame(['result' => false, 'code' => 400], $result);
        $line = $this->getRefusalLogLine();
        $this->assertStringContainsString('HTTP 400', $line);
        $this->assertStringContainsString('The phone number format is invalid.', $line);
        $this->assertStringContainsString('invalid_request_error', $line);
        $this->assertStringContainsString('billing.landline_phone_number', $line);
    }

    public function testPhoneNumbersInTheApiMessageAreMasked()
    {
        $this->class->processPaymentError(422, $this->default_payment_tab, [
            'message' => 'Unprocessable entity.',
            'details' => [
                'message' => 'Phone number +33612345678 is invalid.',
                'details' => [
                    'billing.mobile_phone_number' => '0033 6 12 34 56 78 is not allowed',
                ],
            ],
        ]);

        $line = $this->getRefusalLogLine();
        $this->assertStringContainsString('+33*******78', $line);
        $this->assertStringNotContainsString('+33612345678', $line);
        $this->assertStringNotContainsString('0033 6 12 34 56 78', $line);
    }

    public function testMessageIsLoggedWhenTheApiReturnedNoDetails()
    {
        $this->class->processPaymentError(500, $this->default_payment_tab, [
            'result' => false,
            'code' => 500,
            'message' => 'Cannot connect to the API',
        ]);

        $line = $this->getRefusalLogLine();
        $this->assertStringContainsString('HTTP 500', $line);
        $this->assertStringContainsString('Cannot connect to the API', $line);
    }

    /**
     * @dataProvider malformedApiResponseProvider
     *
     * @param mixed $api_response
     */
    public function testMalformedApiResponseStillLogsTheCode($api_response)
    {
        $this->assertSame(
            ['result' => false, 'code' => 400],
            $this->class->processPaymentError(400, $this->default_payment_tab, $api_response)
        );

        $this->assertStringContainsString('HTTP 400', $this->getRefusalLogLine());
    }

    public function testCodeIsLoggedWithoutApiResponse()
    {
        $this->assertSame(
            ['result' => false, 'code' => 400],
            $this->class->processPaymentError(400, $this->default_payment_tab)
        );

        $this->assertStringContainsString('HTTP 400', $this->getRefusalLogLine());
    }

    public function testPaymentTabIsNeverLogged()
    {
        $this->class->processPaymentError(400, $this->default_payment_tab, [
            'message' => 'Bad request.',
            'details' => [
                'message' => 'The phone number format is invalid.',
            ],
        ]);

        $line = $this->getRefusalLogLine();
        foreach (['jdoe@payplug.com', 'John', 'Doe', 'address name', 'City name', '+33123456789'] as $value) {
            $this->assertStringNotContainsString($value, $line);
        }
    }

    public function malformedApiResponseProvider()
    {
        return [
            'not an array' => ['Bad request.'],
            'null' => [null],
            'details not an array' => [['message' => 'Bad request.', 'details' => 'invalid']],
            'non string message' => [['message' => ['Bad request.'], 'details' => ['message' => 42]]],
        ];
    }

    private function getRefusalLogLine()
    {
        $lines = [];
        $this->logger->shouldHaveReceived('addLog')
            ->with(\Mockery::on(function ($line) use (&$lines) {
                if (false !== strpos($line, 'processPaymentError')) {
                    $lines[$line] = true;
                }

                return true;
            }), 'error');
        $this->assertCount(1, $lines);

        return key($lines);
    }
}
