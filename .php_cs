<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('.')
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder)
;