<?php

$finder = (new PhpCsFixer\Finder())
    ->exclude('test')
    ->exclude('vendor')
    ->exclude('translations')
    ->exclude('.tmp_staging')
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setUsingCache(true)
    ->setRules([
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        'phpdoc_summary' => true,
        'yoda_style' => true,
        'visibility_required' => true,
        'single_blank_line_before_namespace' => true,
        'no_blank_lines_after_phpdoc' => true,
        'single_space_after_construct' => true,
        'no_spaces_after_function_name' => true,
        'concat_space' => ['spacing' => 'one'],
        'include' => true,
        'trailing_comma_in_multiline' => true,
        'binary_operator_spaces' => true,
        'no_unneeded_control_parentheses' => false,
        'cast_spaces' => true,
        'blank_line_before_statement' => true,
        'no_alias_language_construct_call' => true,
        'no_extra_blank_lines' => true,
        'array_syntax' => ['syntax' => 'short'],
        'phpdoc_separation' => true,
        'no_superfluous_elseif' => false,
        'multiline_whitespace_before_semicolons' => false,
        'array_indentation' => false,
        'phpdoc_order' => true,
        'phpdoc_trim' => true,
        'single_line_comment_style' => true,
        'phpdoc_align' => ['align' => 'left'],
        'php_unit_internal_class' => false,
        'php_unit_test_class_requires_covers' => false,
        'phpdoc_types_order' => ['sort_algorithm' => 'alpha', 'null_adjustment' => 'always_last'],
    ])
    ->setFinder($finder)
;
