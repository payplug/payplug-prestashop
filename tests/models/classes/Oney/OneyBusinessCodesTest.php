<?php

namespace PayPlug\tests\models\classes\Oney;

use PayPlug\src\models\classes\Oney\OneyBusinessCodes;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group class
 * @group oney_business_codes_class
 */
final class OneyBusinessCodesTest extends TestCase
{
    const RAW_CODES = [
        'x3_with_fees' => 'W3135',
        'x4_with_fees' => 'W4144',
        'x3_without_fees' => 'DLN04',
        'x4_without_fees' => 'DLN05',
    ];

    public function testToListReturnsAllCodesChargedAndFree()
    {
        $codes = OneyBusinessCodes::fromArray(self::RAW_CODES);

        $this->assertSame(
            ['W3135', 'W4144', 'DLN04', 'DLN05'],
            $codes->toList()
        );
    }

    public function testToListOmitsMissingCodes()
    {
        $codes = OneyBusinessCodes::fromArray([
            'x3_with_fees' => 'W3135',
            'x4_with_fees' => 'W4144',
        ]);

        $this->assertSame(['W3135', 'W4144'], $codes->toList());
    }

    public function testGetReturnsSingleCodeForOperationAndFeeMode()
    {
        $codes = OneyBusinessCodes::fromArray(self::RAW_CODES);

        $this->assertSame('W3135', $codes->get('x3', true));
        $this->assertSame('DLN04', $codes->get('x3', false));
        $this->assertSame('W4144', $codes->get('x4', true));
        $this->assertSame('DLN05', $codes->get('x4', false));
    }

    public function testGetReturnsNullForUnknownOperation()
    {
        $codes = OneyBusinessCodes::fromArray(self::RAW_CODES);

        $this->assertNull($codes->get('x5', true));
    }

    public function testGetReturnsNullWhenCodeMissing()
    {
        $codes = OneyBusinessCodes::fromArray([
            'x3_with_fees' => 'W3135',
        ]);

        $this->assertNull($codes->get('x4', true));
    }

    public function testFromArrayIgnoresEmptyStringCodes()
    {
        $codes = OneyBusinessCodes::fromArray([
            'x3_with_fees' => '',
            'x4_with_fees' => 'W4144',
        ]);

        $this->assertSame(['W4144'], $codes->toList());
        $this->assertNull($codes->get('x3', true));
    }
}
