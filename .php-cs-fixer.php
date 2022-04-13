<?php

$finder = (new PhpCsFixer\Finder)
    ->exclude('test')
    ->exclude('vendor')
    ->exclude('translations')
    ->exclude('.tmp_staging')
    ->in(__DIR__)
;

return (new PhpCsFixer\Config)
    ->setUsingCache(true)
    ->setRules([
        '@PSR12' => true,
        'phpdoc_summary' => false,
        'yoda_style' => false,
        'visibility_required' => false,
    ])
    ->setFinder($finder)
;
