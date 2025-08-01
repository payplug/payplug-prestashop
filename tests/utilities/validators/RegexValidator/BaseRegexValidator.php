<?php

namespace PayPlug\tests\utilities\validators\RegexValidator;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use PayPlug\src\utilities\validators\RegexValidator;
use PayPlug\tests\FormatDataProvider;

/**
 * @internal
 * @coversNothing
 */
class BaseRegexValidator extends MockeryTestCase
{
    use FormatDataProvider;

    public $validator;

    public function setUp()
    {
        $this->validator = \Mockery::mock(RegexValidator::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
    }
}
