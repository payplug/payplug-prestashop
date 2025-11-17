<?php

namespace PayPlug\tests;

trait FormatDataProvider
{
    public function invalidArrayFormatDataProvider()
    {
        yield [42];

        yield [null];

        yield [false];

        yield ['lorem ipsum'];
    }

    public function invalidBoolFormatDataProvider()
    {
        yield ['lorem Ipsum'];

        yield [42];

        yield [['key' => 'value']];

        yield [null];
    }

    public function invalidIntegerFormatDataProvider()
    {
        yield [null];

        yield [['key' => 'value']];

        yield [true];

        yield ['lorem ipsum'];
    }

    public function invalidFloatFormatDataProvider()
    {
        yield [null];

        yield [['key' => 'value']];

        yield [true];

        yield ['lorem ipsum'];

        yield [42];
    }

    public function invalidNumericFormatDataProvider()
    {
        yield [null];

        yield [['key' => 'value']];

        yield [true];

        yield ['lorem ipsum'];

        yield ['123abc'];
    }

    public function invalidJSONFormatDataProvider()
    {
        yield [''];

        yield ['{"feature": \'value\'}'];

        yield ['{"feature": "value", }'];

        yield ['{{}}'];
    }

    public function invalidObjectFormatDataProvider()
    {
        yield [42];

        yield [['key' => 'value']];

        yield [true];

        yield ['lorem ipsum'];
    }

    public function invalidStringFormatDataProvider()
    {
        yield [42];

        yield [['key' => 'value']];

        yield [false];

        yield [null];
    }

    public function invalidRetrieveDataFormatDataProvider()
    {
        yield [42];

        yield [false];

        yield [null];
    }
}
