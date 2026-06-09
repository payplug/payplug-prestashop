<?php

namespace PayPlug\tests\utilities\validators\RegexValidator;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use PayPlug\src\utilities\validators\RegexValidator;
use PayPlug\tests\FormatDataProvider;

/**
 * @internal
 * @coversNothing
 */
abstract class BaseRegexValidator extends MockeryTestCase
{
    use FormatDataProvider;

    public $validator;

    public function setUp(): void
    {
        $this->validator = \Mockery::mock(RegexValidator::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
    }
}
