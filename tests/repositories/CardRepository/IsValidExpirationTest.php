<?php

namespace PayPlug\tests\repositories\CardRepository;

/**
 * @group unit
 * @group repository
 * @group card_repository
 *
 * @runTestsInSeparateProcesses
 */
final class IsValidExpirationTest extends BaseCardRepository
{
    public function invalidDataProvider()
    {
        // invalid int $month
        yield [null, 42];
        yield [false, 42];
        yield ['I am a string!', 42];
        yield [['key' => 'value'], 42];

        // invalid int $year
        yield [42, null];
        yield [42, false];
        yield [42, 'I am a string!'];
        yield [42, ['key' => 'value']];

        // invalid year $year
        yield [1, 30];
        yield [01, 30];

        // expired date
        yield [1, 2020];
        yield [01, 2020];
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param $month
     * @param $year
     */
    public function testWithInvalidParams($month, $year)
    {
        $this->assertFalse($this->repo->isValidExpiration($month, $year));
    }

    public function validDataProvider()
    {
        // expired date
        yield [1, 2030];
        yield [01, 2030];
    }

    /**
     * @dataProvider validDataProvider
     *
     * @param $month
     * @param $year
     */
    public function testWithValidExpirationDate($month, $year)
    {
        $this->assertTrue($this->repo->isValidExpiration($month, $year));
    }
}
