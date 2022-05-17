<?php

namespace Snap\Commands\Make;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class Hookable extends Creator
{
    /**
     * Setup the command signature and help text.
     */
    protected function configure(): void
    {
        $this->setName('make:hookable')
            ->setDescription('Creates a new Hookable.')
            ->setHelp('Creates a new Hookable class within your theme/Hookables directory');

        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the created Hookable.');
    }

    /**
     * Run the command.
     *
     * @throws \Hodl\Exceptions\ContainerException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $created = $this->scaffold(
            'hookable',
            [
                'CLASSNAME' => $input->getArgument('name'),
            ]
        );

        return $this->writeOutput($created, $input, $output);
    }
}
