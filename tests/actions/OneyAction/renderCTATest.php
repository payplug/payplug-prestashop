<?php

namespace PayPlug\tests\actions\OneyAction;

/**
 * @group unit
 * @group action
 * @group oney_action
 *
 * @runTestsInSeparateProcesses
 */
class renderCTATest extends BaseOneyAction
{
    public function setup()
    {
        parent::setUp();
    }

    public function testWhenWrongController()
    {
        $controller = $this->instance->shouldReceive([
            'getController' => 'index',
        ]);
        $this->dispatcher->shouldReceive([
            'getInstance' => $controller,
        ]);

        $this->assertFalse($this->action->renderCTA());
    }

    public function testWhenOneyNotAllowed()
    {
        $controller = $this->instance->shouldReceive([
            'getController' => 'product',
        ]);
        $this->dispatcher->shouldReceive([
            'getInstance' => $controller,
        ]);

        $this->payment_method->shouldReceive([
            'isOneyAllowed' => false,
        ]);

        $this->assertFalse($this->action->renderCTA());
    }

    public function testWhenWrongIsoCode()
    {
        $controller = $this->instance->shouldReceive([
            'getController' => 'product',
        ]);
        $this->dispatcher->shouldReceive([
            'getInstance' => $controller,
        ]);

        $this->payment_method->shouldReceive([
            'isOneyAllowed' => true,
        ]);

        $this->toolsAdapter->shouldReceive([
            'tool' => 'PL',
        ]);

        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('company_iso')
            ->andReturn('FR');

        $this->assertFalse($this->action->renderCTA());
    }

    public function testWhenGoodControllerButWrongHook()
    {
        $controller = $this->instance->shouldReceive([
            'getController' => 'product',
        ]);
        $this->dispatcher->shouldReceive([
            'getInstance' => $controller,
        ]);

        $this->payment_method->shouldReceive([
            'isOneyAllowed' => true,
        ]);

        $this->toolsAdapter->shouldReceive([
            'tool' => 'FR',
        ]);

        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('company_iso')
            ->andReturn('FR');

        $params = [
            'type' => 'test',
        ];

        $this->assertFalse($this->action->renderCTA($params));
    }

    public function testWhenGoodControllerButWrongAction()
    {
        $controller = $this->instance->shouldReceive([
            'getController' => 'product',
        ]);
        $this->dispatcher->shouldReceive([
            'getInstance' => $controller,
        ]);

        $this->payment_method->shouldReceive([
            'isOneyAllowed' => true,
        ]);

        $this->toolsAdapter
            ->shouldReceive('tool')
            ->with('strtoupper', 'fr')
            ->andReturn('FR');
        $this->toolsAdapter
            ->shouldReceive('tool')
            ->with('getValue', 'action')
            ->andReturn('quickview');

        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('company_iso')
            ->andReturn('FR');

        $params = [
            'type' => 'after_price',
        ];

        $this->assertFalse($this->action->renderCTA($params));
    }

    public function testWhenWrongCartHook()
    {
        $controller = $this->instance->shouldReceive([
            'getController' => 'cart',
        ]);
        $this->dispatcher->shouldReceive([
            'getInstance' => $controller,
        ]);

        $this->payment_method->shouldReceive([
            'isOneyAllowed' => true,
        ]);

        $this->toolsAdapter
            ->shouldReceive('tool')
            ->with('strtoupper', 'fr')
            ->andReturn('FR');
        $this->toolsAdapter
            ->shouldReceive('tool')
            ->with('getValue', 'action')
            ->andReturn('test');

        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('company_iso')
            ->andReturn('FR');

        $params = [
            'type' => 'unit_price',
        ];

        $this->assertFalse($this->action->renderCTA($params));
    }
}
