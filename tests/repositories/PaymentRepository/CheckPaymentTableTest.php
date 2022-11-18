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
final class CheckPaymentTableTest extends BasePaymentRepository
{
    /**
     * Parameters to test method with empty $paiementDetails
     *
     * @return \Generator
     */
    public function checkPaymentTableParameters()
    {
        yield [null, 'cart id: null'];
        yield [(string) 'I am a string!', 'cart id: "I am a string!"'];
    }

    /**
     * Test methods with nulled $paiementDetails
     *
     * @dataProvider checkPaymentTableParameters
     *
     * @param array  $parameter
     * @param string $logMessage
     */
    public function testMethodWithEmptyParams($parameter, $logMessage)
    {
        $this->repo
            ->shouldReceive([
                'returnPaymentError' => $logMessage,
            ])
        ;

        $this->assertSame(
            $this->repo->checkPaymentTable($parameter),
            $logMessage
        );
    }

    public function testCheckPaymentWithValidData()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => ['item1', 'item2'],
            ])
        ;

        $this->assertSame(
            'item2',
            $this->repo->checkPaymentTable(1)
        );
    }

    public function testCheckPaymentWithInvalidData()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => false,
            ])
        ;

        $this->assertSame(
            false,
            $this->repo->checkPaymentTable(1)
        );
    }
}
