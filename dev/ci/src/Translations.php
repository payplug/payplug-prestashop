<?php

class Translations
{
    private $files;
    private $method;
    private $moduleName;
    private $trans;

    public function __construct()
    {
        $this->moduleName = $this->getModuleName();
    }

    /**
     * @description Return all module's translations
     *
     * @return array
     */
    public function getTranslations()
    {
        $this->setMethod('l');
        $this->setFiles();
        $this->setTrans();
        $this->fillTranslations();

        return $this->trans;
    }

    /*
     * @description get the module Name from composer.json
     * @return mixed
     */
    public function getModuleName()
    {
        $configuration = json_decode(file_get_contents(dirname(__FILE__) . '/../../../composer.json'));

        return $configuration->moduleName;
    }

    /**
     * @description Get the only file who contain translation
     *
     * @param $files
     * @param string $type_clear
     * @param string $path
     *
     * @return mixed
     */
    private function clearFiles($files, $type_clear = 'file', $path = '')
    {
        // List of directory which not must be parsed
        $arr_exclude = ['img', 'js', 'mails', 'override', 'vendor', 'tests', 'test', 'upgrade'];

        // List of good extention files
        $arr_good_ext = ['.tpl', '.php'];

        foreach ($files as $key => $file) {
            if ($file[0] === '.'
                || $file === 'index.php'
                || in_array(substr($file, 0, strrpos($file, '.')), [])) {
                unset($files[$key]);
            } elseif ($type_clear === 'file'
                && !in_array(substr($file, strrpos($file, '.')), $arr_good_ext)) {
                unset($files[$key]);
            } elseif ($type_clear === 'directory'
                && (!is_dir($path . $file) || in_array($file, $arr_exclude))) {
                unset($files[$key]);
            }
        }

        return $files;
    }

    /**
     * @description Update translation from current files
     */
    private function fillTranslations()
    {
        // get current file
        $translation_dir = dirname(__FILE__) . '/../../../translations/';
        $translation_files = scandir($translation_dir, SCANDIR_SORT_NONE);
        foreach ($translation_files as $file) {
            if ($file[0] === '.'
                || $file === 'index.php'
                || in_array(substr($file, 0, strrpos($file, '.')), [])) {
                continue;
            }

            $this->hydrateFromLangFile($translation_dir, $file);
        }
    }

    /**
     * @description Get file who could countain translation
     *
     * @param $path
     * @param $array_files
     * @param $module_name
     */
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
                    'name' => $file,
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

    /**
     * @description Hydrate translation from adapter file
     *
     * @param bool $path
     * @param bool $file
     *
     * @return bool
     */
    private function hydrateFromLangFile($path = false, $file = false)
    {
        if (!$path || !$file) {
            return false;
        }
        $lang_file = $path . $file;

        if (!file_exists($lang_file)) {
            return false;
        }

        @include $lang_file;

        $translations = $GLOBALS['_MODULE'];
        $lang = substr(basename($file), 0, -4);

        foreach (array_keys($this->trans) as $key) {
            $this->trans[$key][$lang] = isset($translations[$key]) ? $translations[$key] : '';
        }

        return true;
    }

    /**
     * @description Get translation use in a given file
     *
     * @param $content
     * @param bool $type_file
     *
     * @return array
     */
    private function parseFile($content, $type_file = false)
    {
        // Parsing modules file
        if ($type_file == 'php') {
            $regex = '/->' . $this->method . '\(\s*(\')(.*[^\\\\])\'(\s*,\s*?\'(.+)\')?(\s*,\s*?(.+))?\s*\)/Ums';
        } else {
            // In tpl file look for something that should contain mod='module_name' according to the documentation
            $regex = '/\{l\s*s=([\'\"])(.*[^\\\\])\1.*\s+(?:tags=\[(.*)]*\](.*)+)?mod=\'' . $this->moduleName . '\'\.*\}/U';
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
                $tags = str_replace('\'', '', $matches[3][$i]);

                if (isset($matches[6]) && $type_file == 'php') {
                    $alias = str_replace('\'', '', $matches[6][$i]);
                    if ($tags) {
                        $string = ['string' => $string, 'tags' => $tags, 'alias' => $alias];
                    } else {
                        $string = ['string' => $string, 'alias' => $alias];
                    }
                } else {
                    if ($tags) {
                        $string = ['string' => $string, 'tags' => $tags];
                    }
                }

                if ($quote === '"') {
                    // Escape single quotes because the core will do it when looking for the translation of this string
                    $string = str_replace('\'', '\\\'', $string);
                    // Unescape double quotes
                    $string = preg_replace('/\\\\+"/', '"', $string);
                }

                $strings[] = $string;
            }
        }

        return array_unique($strings, SORT_REGULAR);
    }

    /**
     * @description Set the files
     *
     * @return array
     */
    private function setFiles()
    {
        $array_files = [];
        $path = dirname(__FILE__) . '/../../../';
        $this->getRecursiveFiles($path, $array_files, $this->moduleName);

        return $this->files = $array_files;
    }

    /**
     * @description Set the method
     *
     * @param $method
     */
    private function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @description Set the translations
     */
    private function setTrans()
    {
        $this->trans = [];
        $array_check_duplicate = [];
        foreach ($this->files as &$file) {
            if ((preg_match('/^(.*).tpl$/', $file['name']) || preg_match(
                '/^(.*).php$/',
                $file['name']
            )) && file_exists($file_path = $file['path'] . $file['name'])) {
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
                $alias = '';
                foreach ($matches as $key) {
                    $tags = '';

                    if (is_array($key)) {
                        if (isset($key['tags'])) {
                            $tags = $key['tags'];
                        }
                        if (isset($key['alias'])) {
                            $alias = $key['alias'];
                        }
                        $key = $key['string'];
                    }

                    $md5_key = md5($key);

                    $trans_key = '<{' . $this->moduleName . '}prestashop>';
                    if ($alias) {
                        $trans_key .= strtolower($alias) . '_' . $md5_key;
                    } else {
                        $trans_key .= strtolower($template_name) . '_' . $md5_key;
                    }

                    // to avoid duplicate entry
                    if (!in_array($trans_key, $array_check_duplicate)) {
                        $array_check_duplicate[] = $trans_key;
                        $this->trans[$trans_key] = [
                            'default' => $key,
                            'tags' => $tags,
                        ];
                    }
                }
            }
        }
    }
}
