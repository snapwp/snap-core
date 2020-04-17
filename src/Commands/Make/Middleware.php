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
    protected function configure()
    {
        $this->setName('make:middleware')
            ->setDescription('Creates a new middleware.')
            ->setHelp('Creates a new Middleware Hookable within your theme/Http/Middleware directory');

        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the created Middleware.');
    }

    /**
     * Run the command.
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface $output Command output.
     * @throws \Hodl\Exceptions\ContainerException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $created = $this->scaffold(
            'middleware',
            [
                'CLASSNAME' => $input->getArgument('name'),
            ]
        );

        if ($created === true) {
            $output->writeln("<info>{$input->getArgument('name')} was created successfully</info>");
        } else {
            $output->writeln("<error>{$input->getArgument('name')} could not be created</error>");
        }
    }
}
