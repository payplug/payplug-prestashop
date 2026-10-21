<?php

namespace PayPlug\tests\controllers;

use PHPUnit\Framework\TestCase;

/**
 * ModuleAdminController is not available in tests, so the controller source is inspected instead of instantiated.
 * This is a regression guard, not a behaviour test: it is brittle by design (a renamed method or a
 * Referer read moved to another file would slip through). Replace it with a real controller test
 * once the admin controllers can be loaded in the unit suite.
 *
 * @group unit
 * @group controller
 * @group admin_controller
 */
class AdminPayplugControllerRefererTest extends TestCase
{
    private $source;

    public function setUp(): void
    {
        $this->source = file_get_contents(__DIR__ . '/../../controllers/admin/AdminPayplugController.php');
    }

    public function testControllerNeverReadsHttpReferer()
    {
        $this->assertStringNotContainsString('HTTP_REFERER', $this->source);
    }

    public function testConstructorDoesNotDispatchRestRoutes()
    {
        $this->assertStringNotContainsString('renderApiRest', $this->getMethodBody('__construct'));
    }

    public function testInitContentStillDispatchesRestRoutes()
    {
        $this->assertStringContainsString('renderApiRest', $this->getMethodBody('initContent'));
    }

    /**
     * @param string $method_name
     *
     * @return string
     */
    private function getMethodBody($method_name)
    {
        $tokens = token_get_all($this->source);
        $count = count($tokens);
        for ($i = 0; $i < $count; ++$i) {
            if (!is_array($tokens[$i]) || T_FUNCTION !== $tokens[$i][0]) {
                continue;
            }
            $j = $i + 1;
            while (is_array($tokens[$j]) && T_WHITESPACE === $tokens[$j][0]) {
                ++$j;
            }
            if (!is_array($tokens[$j]) || $method_name !== $tokens[$j][1]) {
                continue;
            }
            while ('{' !== $tokens[$j]) {
                ++$j;
            }

            $body = '';
            $depth = 0;
            for (; $j < $count; ++$j) {
                $text = is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
                if ('{' === $text || (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
                    ++$depth;
                } elseif ('}' === $text) {
                    --$depth;
                }
                $body .= $text;
                if (0 === $depth) {
                    return $body;
                }
            }
        }

        $this->fail(sprintf('Method %s not found in AdminPayplugController', $method_name));
    }
}
