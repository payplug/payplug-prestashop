<?php

namespace PayPlug\tests\actions\OneyAction;

/**
 * @group unit
 * @group action
 * @group oney_action
 *
 * @runTestsInSeparateProcesses
 */
class renderRequiredFieldsTest extends BaseOneyAction
{
    public function setup()
    {
        parent::setUp();
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $param
     */
    public function testWhenGivenParamIsInvalidStringFormat($param)
    {
        $this->assertSame(
            [],
            $this->action->renderRequiredFields($param)
        );
    }
}
