<?php

namespace PayPlug\tests\controllers;

use PHPUnit\Framework\TestCase;

/**
 * ModuleAdminController is not available in tests, so the controller source is inspected instead of instantiated.
 *
 * @group unit
 * @group controller
 * @group admin_controller
 */
class AdminPayplugControllerRefundTest extends TestCase
{
    private $source;

    public function setUp(): void
    {
        $this->source = file_get_contents(__DIR__ . '/../../controllers/admin/AdminPayplugController.php');
    }

    public function testRetailRefundAmountIsComputedOnlyFromThePaymentResource()
    {
        $this->assertSame(1, substr_count($this->source, '$amount_formated ='));
        $this->assertStringNotContainsString('payplug.utilities.helper.amount', $this->source);
    }

    public function testUnifiedRefundIsDispatchedBeforeTheRetailAmountLookup()
    {
        $unified_branch = strpos($this->source, "'uhf' === \$this->tools->tool('getValue', 'payment_type')");
        $retail_lookup = strpos($this->source, '->getRefundAmountInCents(');

        $this->assertNotFalse($unified_branch);
        $this->assertNotFalse($retail_lookup);
        $this->assertLessThan($retail_lookup, $unified_branch);
    }
}
