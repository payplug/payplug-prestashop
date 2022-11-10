<?php
/**
 * 2013 - 2021 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    PayPlug SAS
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\utilities\helpers;

use PayPlug\classes\DependenciesClass;

class FilesHelper
{
    /**
     * @description
     */
    public static function clean()
    {
        $dependencies = new DependenciesClass();
        $logger = $dependencies->getPlugin()->getLogger();

        $current_list = self::get();
        $allow_list = self::getList();

        $files_to_remove = array_diff($current_list, $allow_list);
        if (!empty($files_to_remove)) {
            $logger->addLog('FilesHelper: Clean outters files');
            $logger->addLog('$files: ' . json_encode($files_to_remove));

            foreach ($files_to_remove as $file) {
                $path = dirname(__FILE__) . '/../../../' . $file;
                if (file_exists($path)) {
                    if (!unlink($path)) {
                        $logger->addLog('unable to delete file: ' . $file);
                    }
                } else {
                    $logger->addLog('unable to find file: ' . $file);
                }
            }

            $logger->addLog('End of cleaning');
        }
    }

    /**
     * @description Get the list of all the files from a given dir
     *
     * @param string $dir
     *
     * @return array
     */
    public static function get($dir = '')
    {
        if (!is_string($dir)) {
            return [];
        }

        $module_files = [];
        $path = dirname(__FILE__) . '/../../../' . ($dir ? $dir : '');
        if (!is_dir($path)) {
            return [];
        }
        $module_files = self::getRecursiveFiles($path, $module_files);

        $list = [];
        foreach ($module_files as $file) {
            $list[$file] = str_replace($path, '', $file);
        }

        sort($list);

        return $list;
    }

    /**
     * @description Get the list of all the module files from the whitelist
     *
     * @return array
     */
    private static function getList()
    {
        // Hydrate $translations from CSV file
        $files = [];
        $path = dirname(__FILE__) . '/../../../module_files.csv';

        if (file_exists($path)) {
            if ($csvfile = fopen($path, 'r')) {
                $count = 0;
                while (($line = fgetcsv($csvfile, 0, ';')) !== false) {
                    $files[] = $line[0];
                }
            }
        }

        return $files;
    }

    /**
     * @description Filter the type of files from a given array
     *
     * @param array  $files
     * @param string $type_clear
     * @param string $path
     *
     * @return array
     */
    private static function clearFiles($files = [], $type_clear = 'file', $path = '')
    {
        if (!$files || !is_array($files)) {
            return [];
        }

        if (!$path || !is_dir($path)) {
            return [];
        }

        // List of directory which not must be parsed
        $arr_exclude = [
            '.git',
            '.idea',
        ];

        foreach ($files as $key => $file) {
            if (in_array($file, ['.', '..'])) {
                unset($files[$key]);
            } elseif ($type_clear === 'file' && is_dir($path . $file)) {
                unset($files[$key]);
            } elseif ($type_clear === 'directory'
                && (!is_dir($path . $file) || in_array($file, $arr_exclude))) {
                unset($files[$key]);
            }
        }

        return $files;
    }

    /**
     * @description Get recursives files from a given path
     *
     * @param $path
     * @param $array_files
     *
     * @return array
     */
    private static function getRecursiveFiles($path, &$array_files = [])
    {
        if (!$path || !is_dir($path)) {
            return [];
        }

        if (!is_array($array_files)) {
            return [];
        }

        $files = [];
        if (file_exists($path)) {
            $files = scandir($path, SCANDIR_SORT_NONE);
        }

        $files_for_module = self::clearFiles($files, 'file', $path);
        if (!empty($files_for_module)) {
            foreach ($files_for_module as $file) {
                $array_files[] = $path . $file;
            }
        }

        $dir_module = self::clearFiles($files, 'directory', $path);
        if (!empty($dir_module)) {
            foreach ($dir_module as $folder) {
                self::getRecursiveFiles($path . $folder . '/', $array_files);
            }
        }

        return $array_files;
    }
}
