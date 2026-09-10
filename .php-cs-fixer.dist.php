<?php

declare(strict_types=1);

/**
 * Code style: PER-CS 2.0 with strict types and ordered imports.
 *
 * The legacy codebase mixed two- and three-space indentation, Hungarian and
 * English identifiers, and four different file layouts. A formatter removes that
 * as a topic of discussion.
 */

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__ . '/apps/api/src',
        __DIR__ . '/apps/api/bootstrap',
        __DIR__ . '/apps/api/config',
        __DIR__ . '/apps/api/tests',
        __DIR__ . '/packages',
    ])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        '@PHP84Migration' => true,

        'declare_strict_types' => true,
        'strict_param' => true,
        'strict_comparison' => true,

        'global_namespace_import' => ['import_classes' => true, 'import_functions' => false, 'import_constants' => false],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,

        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters', 'match']],
        'single_quote' => true,
        'native_function_invocation' => false,

        'phpdoc_align' => ['align' => 'left'],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
    ])
    ->setFinder($finder);
