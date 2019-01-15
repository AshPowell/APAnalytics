<?php

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        'psr0'                               => false,
        '@PSR2'                              => true,
        'blank_line_after_namespace'         => true,
        'braces'                             => true,
        'class_definition'                   => true,
        'elseif'                             => true,
        'function_declaration'               => true,
        'indentation_type'                   => true,
        'line_ending'                        => true,
        'lowercase_constants'                => true,
        'lowercase_keywords'                 => true,
        'method_argument_space'              => ['ensure_fully_multiline' => true],
        'no_break_comment'                   => true,
        'no_closing_tag'                     => true,
        'no_spaces_after_function_name'      => true,
        'no_spaces_inside_parenthesis'       => true,
        'no_trailing_whitespace'             => true,
        'no_trailing_whitespace_in_comment'  => true,
        'single_blank_line_at_eof'           => true,
        'single_class_element_per_statement' => [
            'elements' => ['property'],
        ],
        'single_import_per_statement'    => true,
        'single_line_after_imports'      => true,
        'switch_case_semicolon_to_colon' => true,
        'switch_case_space'              => true,
        'visibility_required'            => true,
        'encoding'                       => true,
        'full_opening_tag'               => true,
        'array_syntax'                   => ['syntax' => 'short'],
        'combine_consecutive_unsets'     => true,
        // one should use PHPUnit methods to set up expected exception instead of annotations
        'general_phpdoc_annotation_remove'    => ['expectedException', 'expectedExceptionMessage', 'expectedExceptionMessageRegExp'],
        'no_extra_consecutive_blank_lines'    => ['break', 'continue', 'extra', 'return', 'throw', 'use', 'parenthesis_brace_block', 'square_brace_block', 'curly_brace_block'],
        'binary_operator_spaces'              => ['default' => 'align'],
        'no_useless_else'                     => true,
        'no_useless_return'                   => true,
        'single_quote'                        => true,
        'no_unused_imports'                   => true,
        'phpdoc_align'                        => true,
        'ordered_class_elements'              => true,
        'ordered_imports'                     => true,
        'phpdoc_add_missing_param_annotation' => true,
        'is_null'                             => true,
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude('tests/Fixtures')
            ->in(__DIR__)
    )
;
