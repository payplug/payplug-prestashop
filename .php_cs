<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('test/')
    ->exclude('tests/')
    ->exclude('translations/')
    //->exclude('src/')
    ->exclude('vendor/')
    //->notPath('payplug.php')
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
