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
     * @description test renderRequiredFields
     * when wrong param is given
     *
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $param
     */
    public function testWhenGivenParamIsInvalidStringFormat($param)
    {
        $controller = $this->instance->shouldReceive([
                                                         'getController' => 'product',
                                                     ]);
        $this->dispatcher->shouldReceive([
                                             'getInstance' => $controller,
                                         ]);
        $this->assertSame(
            [],
            $this->action->renderRequiredFields($param)
        );
    }
}
