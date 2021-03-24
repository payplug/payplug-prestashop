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

namespace PayPlug\src\repositories;

class TranslationsRepository extends Repository
{
    protected $payplug;

    private $method;
    private $files;
    private $trans;

    public function __construct($payplug)
    {
        $this->payplug = $payplug;
    }

    public function translate($id)
    {
        if (!is_int($id)) {
            return false;
        }

        $translation = [
            // controllers/front/ajax.php
            1 => $this->l('Empty payment data'),
            2 => $this->l('At least one of the fields is not correctly completed.'),
            3 => $this->l('Your information has been saved'),
            4 => $this->l('An error occurred. Please retry in few seconds.'),
            5 => $this->l('Oney is momentarily unavailable.'),
        ];

        return $translation[$id];
    }

    private function clearFiles($files, $type_clear = 'file', $path = '')
    {
        // List of directory which not must be parsed
        $arr_exclude = ['img', 'js', 'mails', 'override', 'vendor', 'tests', 'test', 'upgrade'];

        // List of good extention files
        $arr_good_ext = ['.tpl', '.php'];

        foreach ($files as $key => $file) {
            if ($file[0] === '.' || $file === 'index.php' || in_array(substr($file, 0, strrpos($file, '.')), [])) {
                unset($files[$key]);
            } elseif ($type_clear === 'file' && !in_array(substr($file, strrpos($file, '.')), $arr_good_ext)) {
                unset($files[$key]);
            } elseif ($type_clear === 'directory' && (!is_dir($path . $file) || in_array($file, $arr_exclude))) {
                unset($files[$key]);
            }
        }

        return $files;
    }

    private function fillTranslations()
    {
        // get current file
        $translation_dir = dirname(__FILE__) . '/../../translations/';
        $translation_files = scandir($translation_dir, SCANDIR_SORT_NONE);
        foreach ($translation_files as $key => $file) {
            if ($file[0] === '.' || $file === 'index.php' || in_array(substr($file, 0, strrpos($file, '.')), [])) {
                continue;
            }

            $this->hydrateFromLangFile($translation_dir, $file);
        }
    }

    private function getRecursiveFiles($path, &$array_files, $module_name)
    {
        $files = [];
        if (file_exists($path)) {
            $files = scandir($path, SCANDIR_SORT_NONE);
        }

        $files_for_module = $this->clearFiles($files, 'file');
        if (!empty($files_for_module)) {
            foreach ($files_for_module as $file) {
                $array_files[] = [
                    'path' => $path,
                    'name' => $file
                ];
            }
        }

        $dir_module = $this->clearFiles($files, 'directory', $path);

        if (!empty($dir_module)) {
            foreach ($dir_module as $folder) {
                $this->getRecursiveFiles($path . $folder . '/', $array_files, $module_name);
            }
        }
    }

    public function getTranslations()
    {
        $this->setMethod('l');
        $this->setFiles();
        $this->setTrans();
        $this->fillTranslations();
        return $this->trans;
    }

    private function hydrateFromLangFile($path, $file)
    {
        $lang_file = $path . $file;
        $lang = substr(basename($file), 0, -4);

        @include $lang_file;

        $translations = $GLOBALS['_MODULE'];

        foreach ($this->trans as $key => &$trans) {
            $this->trans[$key][$lang] = isset($translations[$key]) ? $translations[$key] : '';
        }
    }

    private function parseFile($content, $type_file = false)
    {
        // Parsing modules file
        if ($type_file == 'php') {
            $regex = '/->' . $this->method . '\(\s*(\')(.*[^\\\\])\'(\s*,\s*?\'(.+)\')?(\s*,\s*?(.+))?\s*\)/Ums';
        } else {
            // In tpl file look for something that should contain mod='module_name' according to the documentation
            $regex = '/\{l\s*s=([\'\"])(.*[^\\\\])\1.*\s+mod=\'payplug\'.*\}/U';
        }

        if (!is_array($regex)) {
            $regex = [$regex];
        }

        $strings = [];
        foreach ($regex as $regex_row) {
            $matches = [];
            $n = preg_match_all($regex_row, $content, $matches);
            for ($i = 0; $i < $n; ++$i) {
                $quote = $matches[1][$i];
                $string = $matches[2][$i];

                if ($quote === '"') {
                    // Escape single quotes because the core will do it when looking for the translation of this string
                    $string = str_replace('\'', '\\\'', $string);
                    // Unescape double quotes
                    $string = preg_replace('/\\\\+"/', '"', $string);
                }

                $strings[] = $string;
            }
        }

        return array_unique($strings);
    }

    private function setFiles()
    {
        $array_files = [];
        $path = dirname(__FILE__) . '/../../';
        $this->getRecursiveFiles($path, $array_files, 'payplug');
        return $this->files = $array_files;
    }

    private function setMethod($method)
    {
        $this->method = $method;
    }

    private function setTrans()
    {
        $this->trans = [];
        $array_check_duplicate = [];
        foreach ($this->files as &$file) {
            if ((preg_match('/^(.*).tpl$/', $file['name']) || preg_match('/^(.*).php$/',
                        $file['name'])) && file_exists($file_path = $file['path'] . $file['name'])) {
                // Get content for this file
                $content = file_get_contents($file_path);

                // Module files can now be ignored by adding this string in a file
                if (strpos($content, 'IGNORE_THIS_FILE_FOR_TRANSLATION') !== false) {
                    continue;
                }

                // Get file type
                $type_file = substr($file['name'], -4) == '.tpl' ? 'tpl' : 'php';

                // Parse this content
                $matches = $this->parseFile($content, $type_file);

                // Write each translation on its module file
                $template_name = substr(basename($file['name']), 0, -4);

                foreach ($matches as $key) {
                    $md5_key = md5($key);
                    $trans_key = '<{payplug}prestashop>' . strtolower($template_name) . '_' . $md5_key;

                    // to avoid duplicate entry
                    if (!in_array($trans_key, $array_check_duplicate)) {
                        $array_check_duplicate[] = $trans_key;
                        $this->trans[$trans_key] = [
                            'default' => $key
                        ];
                    }
                }
            }
        }
    }
}
