<?php

namespace PayPlug\tests\utilities;

use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group scalapay_widget_guard
 */
class ScalapayWidgetSourceGuardTest extends TestCase
{
    const WIDGET_SCRIPT_URL = 'https://cdn.scalapay.com/widget/v5/js/scalapay-widget.js';

    public function testTheVersionFiveScriptUrlIsTheOnlyScalapayCdnUrl()
    {
        $occurrences = [];
        foreach ($this->getSourceFiles(['src', 'classes', 'controllers', 'config', 'views/templates', 'dev/js', 'payplug.php']) as $file) {
            preg_match_all('#[a-z]*:?//cdn\.scalapay\.com[^\s\'"<>]*#i', file_get_contents($file), $matches);
            foreach ($matches[0] as $url) {
                $occurrences[] = $this->relativePath($file) . ': ' . $url;
            }
        }

        $this->assertSame(['src/actions/ScalapayWidgetAction.php: ' . self::WIDGET_SCRIPT_URL], $occurrences);
    }

    public function testNoOlderScalapayWidgetIsReferenced()
    {
        foreach ($this->getSourceFiles(['src', 'classes', 'controllers', 'config', 'views/templates', 'dev/js', 'payplug.php']) as $file) {
            $content = file_get_contents($file);
            $this->assertNotRegExp('#scalapay-widget-loader#i', $content, $this->relativePath($file));
            $this->assertNotRegExp('#scalapay\.com/widget/v[0-4]\b#i', $content, $this->relativePath($file));
            $this->assertNotRegExp('#scalapay\.com/widget/(?!v5/)#i', $content, $this->relativePath($file));
        }
    }

    public function testNoStylesheetTargetsTheScalapayWidget()
    {
        foreach ($this->getSourceFiles(['dev/css', 'views/css']) as $file) {
            $this->assertStringNotContainsStringIgnoringCase('scalapay', file_get_contents($file), $this->relativePath($file));
        }
    }

    public function testTheWidgetTemplatesOnlyHoldScalapayMarkup()
    {
        $templates = glob($this->getRoot() . '/views/templates/hook/scalapay/*.tpl');
        $this->assertCount(2, $templates);

        foreach ($templates as $file) {
            $markup = preg_replace('/\{\*.*?\*\}/s', '', file_get_contents($file));
            $name = $this->relativePath($file);

            $this->assertStringNotContainsString('style=', $markup, $name);
            $this->assertStringNotContainsString('class=', $markup, $name);
            $this->assertStringNotContainsString('{l ', $markup, $name);
            $this->assertSame('', trim(preg_replace('/<[^>]*>/', '', $markup)), $name . ' must not hold any text');
        }
    }

    /**
     * @param string[] $paths relative to the module root
     *
     * @return string[]
     */
    private function getSourceFiles($paths)
    {
        $files = [];
        foreach ($paths as $path) {
            $absolute = $this->getRoot() . '/' . $path;
            if (is_file($absolute)) {
                $files[] = $absolute;

                continue;
            }
            if (!is_dir($absolute)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($absolute, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (preg_match('/\.(php|tpl|js|yml|less|css)$/', $file->getFilename())) {
                    $files[] = $file->getPathname();
                }
            }
        }
        sort($files);

        return $files;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function relativePath($file)
    {
        return substr($file, strlen($this->getRoot()) + 1);
    }

    /**
     * @return string
     */
    private function getRoot()
    {
        return dirname(__DIR__, 2);
    }
}
