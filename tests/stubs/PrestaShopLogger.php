<?php

// PrestaShop core class, not defined by this test suite's bootstrap. Stubbed
// here (global namespace, matching the real class) so tests can exercise
// code paths that call \PrestaShopLogger::addLog() without a full PrestaShop
// runtime.
if (!class_exists('PrestaShopLogger')) {
    class PrestaShopLogger
    {
        public static $logs = [];

        public static function addLog($message, $severity = 1, $error_code = null, $object_type = null, $object_id = null, $allow_duplicate = false)
        {
            self::$logs[] = [
                'message' => $message,
                'severity' => $severity,
                'error_code' => $error_code,
                'object_type' => $object_type,
                'object_id' => $object_id,
                'allow_duplicate' => $allow_duplicate,
            ];
        }
    }
}
