<?php

namespace PayPlug\tests\actions\UnifiedAliasAction;

/**
 * @group unit
 * @group action
 * @group unified_alias_action
 */
class isExpiredTest extends BaseUnifiedAliasAction
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
