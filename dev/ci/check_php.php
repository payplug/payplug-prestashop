<?php

// Get all php file in module
function getPath($list = [], $path = false)
{
    if (!$path) {
        $path = dirname(dirname(__FILE__));
    }

    $files = array_diff(scandir($path), ['..', '.']);

    if (count($files) < 1) {
        return $list;
    }

    foreach ($files as $file) {
        $file_path = $path . '/' . $file;
        $path_parts = pathinfo($file_path);
        $ext = isset($path_parts['extension']) ? $path_parts['extension'] : false;

        if (is_dir($file_path)) {
            $list = getPath($list, $file_path);
        } elseif ('php' == $ext && $file != 'index.php') {
            $list[] = $file_path;
        }
    }

    return $list;
}
$files = getPath();

// Check if given file as syntax error
$errors = [];
$need_error_return = false;
foreach ($files as $path) {
    $check = shell_exec('php -l ' . $path);

    // if some error are detected, from simple warning to fatal error...
    if (strpos($check, 'No syntax errors detected') === false) {
        if (strpos($check, 'Fatal error: ') !== false) {
            $need_error_return = true;
            $errors[] = $check;
        } else {
            $errors[] = $check;
        }
    }
}

// If error are detected return code, or...
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo $error . "\n";
    }

    // Return error
    if ($need_error_return) {
        exit(1);
    }

    // Return warning
    exit(137);
}

// ... return ok
exit(0);
