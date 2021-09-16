<?php

$finder = (new PhpCsFixer\Finder)
    ->exclude('test')
    ->exclude('vendor')
    ->exclude('translations')
    ->in(__DIR__)
;

return (new PhpCsFixer\Config)
    ->setUsingCache(false)
    ->setRules([
        '@PSR2' => true,
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        'strict_param' => false,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder)
;
