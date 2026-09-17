<?php

// PrestaShop core class, not defined by this test suite's bootstrap. Stubbed
// here (global namespace, matching the real class) so tests can exercise
// code paths that call \PrestaShopLogger::addLog() without a full PrestaShop
// runtime.
if (!class_exists('PrestaShopLogger')) {
    class PrestaShopLogger
    {
        public static function addLog($message, $severity = 1, $error_code = null, $object_type = null, $object_id = null, $allow_duplicate = false)
        {
        }
    }
}
