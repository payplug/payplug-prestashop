<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('test')
    ->exclude('vendor')
    ->exclude('translations')
    ->in(__DIR__)
;

return PhpCsFixer\Config::create()
    ->setUsingCache(false)
    ->setRules([
        '@PSR2' => true,
        'strict_param' => false,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder)
;
