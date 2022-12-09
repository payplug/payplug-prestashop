<?php

namespace PayPlug\tests\repositories\PaymentRepository;

/**
 * @group unit
 * @group repository
 * @group payment
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
final class ReturnPaymentErrorTest extends BasePaymentRepository
{
    private $response;

    public function setUp()
    {
        parent::setUp();

        $this->response = [
            'result' => false,
            'name' => 'value',
            'response' => 'error message',
        ];
    }

    public function InvalidElementDataProvider()
    {
        yield [null];
        yield ['wrong_parameter'];
        yield [42];
    }

    /**
     * @dataProvider InvalidElementDataProvider
     *
     * @param mixed $element
     */
    public function testMethodWithEmptyElement($element)
    {
        $this->assertSame(
            [
                'result' => false,
                'response' => 'Test with empty $element params',
            ],
            $this->repo->returnPaymentError($element, 'Test with empty $element params')
        );
    }

    public function InvalidMessageDataProvider()
    {
        yield [null];
        yield [['wrong error message']];
        yield [42];
    }

    /**
     * @dataProvider InvalidMessageDataProvider
     *
     * @param mixed $message
     */
    public function testMethodWithWrongMessage($message)
    {
        $element = [
            'name' => 'unit_test',
            'value' => 'empty message',
        ];

        $this->assertSame(
            [
                'result' => false,
                'unit_test' => '"empty message"',
                'response' => '[PaymentRepository] Error during payment creation process.',
            ],
            $this->repo->returnPaymentError($element, $message)
        );
    }

    public function testMethodWithValidData()
    {
        $element = [
            'name' => 'unit_test',
            'value' => 'empty message',
        ];

        $message = 'Test valide return error method';

        $this->assertSame(
            [
                'result' => false,
                'unit_test' => '"empty message"',
                'response' => 'Test valide return error method',
            ],
            $this->repo->returnPaymentError($element, $message)
        );

        $this->assertSame(
            2,
            count($this->arrayLogger)
        );
    }
}
