<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->in(__DIR__);

return (new PhpCsFixer\Config())
	->setRiskyAllowed(true)
    ->setRules([
        'native_function_invocation' => true,
    ])
    ->setFinder($finder);