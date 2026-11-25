<?php

namespace PayPlug\tests\views;

use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group views
 */
class templatesTest extends TestCase
{
    const SKIPPED_ROOT_DIRECTORIES = ['.git', '.idea', '.tmp_staging', 'dev', 'docs', 'node_modules', 'tests', 'vendor'];

    public function testNoTemplateDisablesVariableEscaping()
    {
        $templates = $this->getModuleTemplates(dirname(__DIR__, 2));
        $this->assertNotEmpty($templates);

        $occurrences = [];
        foreach ($templates as $relative_path => $path) {
            foreach (file($path) as $index => $line) {
                if (preg_match('/\bnofilter\b/i', $line)) {
                    $occurrences[] = $relative_path . ':' . ($index + 1);
                }
            }
        }

        $this->assertSame(
            [],
            $occurrences,
            'nofilter is refused by the PrestaShop Addons validator (removing variable escaping lets malicious code be '
            . 'displayed and executed): escape the variable for its context instead'
        );
    }

    /**
     * @param string $root
     *
     * @return array relative path => absolute path
     */
    private function getModuleTemplates($root)
    {
        $directories = new \RecursiveCallbackFilterIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            function (\SplFileInfo $file) use ($root) {
                return !($file->isDir()
                    && $file->getPath() === $root
                    && in_array($file->getFilename(), self::SKIPPED_ROOT_DIRECTORIES, true));
            }
        );

        $templates = [];
        foreach (new \RecursiveIteratorIterator($directories) as $file) {
            if ('tpl' === $file->getExtension()) {
                $templates[substr($file->getPathname(), strlen($root) + 1)] = $file->getPathname();
            }
        }
        ksort($templates);

        return $templates;
    }
}
