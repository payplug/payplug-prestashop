<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('test')
    ->exclude('vendor')
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PSR2' => true,
        'strict_param' => false,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder)
;
