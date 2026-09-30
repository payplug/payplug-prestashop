<?php

namespace PayPlug\tests\actions\AliasAction;

/**
 * @group unit
 * @group action
 * @group alias_action
 */
class isExpiredTest extends BaseAliasAction
{
    public function testReturnsFalseForAnUnexpiredAlias()
    {
        $this->assertFalse($this->action->isExpired($this->aliasRow()));
    }

    public function testReturnsTrueForAnExpiredAlias()
    {
        $this->assertTrue($this->action->isExpired($this->aliasRow(['exp_month' => '01', 'exp_year' => '2020'])));
    }
}
