<?php

namespace Snap\Commands\Make;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Ajax extends Creator
{
    /**
     * Setup the command signature and help text.
     */
    protected function configure(): void
    {
        $this->setName('make:ajax')
            ->setDescription('Creates a new Ajax_Handler Hookable.')
            ->setHelp('Creates a new Ajax_Handler class within your theme/Http/Ajax directory');

        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the created Ajax_Handler.');

        $this->addOption(
            'action',
            'a',
            InputOption::VALUE_REQUIRED,
            'The action for the created AJAX hook. Defaults to the snake_case class name.'
        );
    }

    /**
     * Run the command.
     *
     * @throws \Hodl\Exceptions\ContainerException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $created = $this->scaffold(
            'ajax',
            [
                'CLASSNAME' => $input->getArgument('name'),
            ],
            [
                'ACTION' => $input->getOption('action'),
            ]
        );

        return $this->writeOutput($created, $input, $output);
    }
}
