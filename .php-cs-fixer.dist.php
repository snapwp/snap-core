<?php

$finder = (new PhpCsFixer\Finder())
    ->exclude('vendor')
    ->in(__DIR__);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        // Prefix all internal PHP functions, and leave existing prefixes (such as on WordPress functions) alone.
        'native_function_invocation' => [
            'include' => ['@internal'],
            'strict' => false,
        ],
    ])
    ->setFinder($finder);
