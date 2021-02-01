<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('translations/')
    ->exclude('vendor/')
    ->in(__DIR__)
;

return PhpCsFixer\Config::create()
    ->setUsingCache(false)
    ->setRules([
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder)
;
