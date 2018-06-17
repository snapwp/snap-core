<?php

namespace Snap\Core\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Creates a controller class in the current directory.
 *
 * @since  1.0.0
 */
class Create_Controller extends Creator
{
    /**
     * Setup the command signature and help text.
     *
     * @since  1.0.0
     */
    protected function configure()
    {
        $this->setName('create:controller')
            ->setDescription('Creates a new Controller.')
            ->setHelp('Creates a new Controller class within your theme/Controllers directory');

        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the created controller.');
    }

    /**
     * Run the command.
     *
     * @since  1.0.0
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $created = $this->scaffold(
            'controller',
            $input->getArgument('name'),
            [
                'CLASSNAME' => $input->getArgument('name'),
            ]
        );

        if ($created === true) {
            $output->writeln('<info>'.$input->getArgument('name').' was created successfully</info>');
        } else {
            $output->writeln('<error>'.$input->getArgument('name').' could not be created</error>');
        }
    }
}