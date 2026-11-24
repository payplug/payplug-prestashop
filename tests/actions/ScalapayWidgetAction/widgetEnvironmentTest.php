<?php

namespace PayPlug\tests\actions\ScalapayWidgetAction;

use PayPlug\src\actions\ScalapayWidgetAction;

/**
 * @group unit
 * @group action
 * @group scalapay_widget_action
 */
class widgetEnvironmentTest extends BaseScalapayWidgetAction
{
    public function setUp(): void
    {
        parent::setUp();

        $this->controller = 'cart';
    }

    public function testTheRepositoryKeepsTheBuildEnvironmentEmpty()
    {
        $this->assertSame('', ScalapayWidgetAction::WIDGET_ENVIRONMENT);
    }

    public function testTheBuildEnvironmentWinsOverTheLocalSetting()
    {
        $this->action = \Mockery::mock(IntegrationBuildScalapayWidgetAction::class)->makePartial();
        $this->action->dependencies = $this->dependencies;

        $this->assertSame('<scalapay/widget.tpl>', $this->action->renderCart());
        $this->assertSame('integration', $this->assigned['scalapay_environment']);
    }

    public function localSettingProvider()
    {
        yield 'integration' => ['integration'];

        yield 'none' => [''];
    }

    /**
     * @dataProvider localSettingProvider
     *
     * @param string $local_environment
     */
    public function testTheLocalSettingIsUsedWithoutBuildEnvironment($local_environment)
    {
        $this->widget_environment = $local_environment;

        $this->assertSame('<scalapay/widget.tpl>', $this->action->renderCart());
        $this->assertSame($local_environment, $this->assigned['scalapay_environment']);
    }
}
