<?php

namespace Snap\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Creates an Ajax class in the current directory.
 *
 * @since  1.0.0
 */
class Create_Ajax extends Creator
{
    /**
     * Setup the command signature and help text.
     *
     * @since  1.0.0
     */
    protected function configure()
    {
        $this->setName('make:ajax')
            ->setDescription('Creates a new Ajax Hookable.')
            ->setHelp('Creates a new Ajax class within your theme/Ajax directory');

        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the created ajax hookable.');
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
     * @since  1.0.0
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $created = $this->scaffold(
            'ajax',
            $input->getArgument('name'),
            [
                'CLASSNAME' => $input->getArgument('name'),
            ],
            [
                'ACTION' => $input->getOption('action'),
            ]
        );

        if ($created === true) {
            $output->writeln('<info>'.$input->getArgument('name').' was created successfully</info>');
        } else {
            $output->writeln('<error>'.$input->getArgument('name').' could not be created</error>');
        }
    }
}
