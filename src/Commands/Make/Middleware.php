<?php

namespace Snap\Commands\Make;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Creates a Middleware class in the current directory.
 */
class Middleware extends Creator
{
    /**
     * Setup the command signature and help text.
     */
    protected function configure(): void
    {
        $this->setName('make:middleware')
            ->setDescription('Creates a new middleware.')
            ->setHelp('Creates a new Middleware Hookable within your theme/Http/Middleware directory');

        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the created Middleware.');
    }

    /**
     * Run the command.
     *
     * @throws \Hodl\Exceptions\ContainerException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $created = $this->scaffold(
            'middleware',
            [
                'CLASSNAME' => $input->getArgument('name'),
            ]
        );

        return $this->writeOutput($created, $input, $output);
    }
}
