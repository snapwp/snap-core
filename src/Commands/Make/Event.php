<?php

namespace Snap\Commands\Make;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Creates an Event class in the current directory.
 *
 * @since  1.0.0
 */
class Event extends Creator
{
    /**
     * Setup the command signature and help text.
     *
     * @since  1.0.0
     */
    protected function configure()
    {
        $this->setName('make:event')
            ->setDescription('Creates a new Cron_Event.')
            ->setHelp('Creates a new Cron_Event Hookable within your theme/Events directory');

        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the created Cron_Event.');
    }

    /**
     * Run the command.
     *
     * @since  1.0.0
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface $output Command output.
     * @throws \Hodl\Exceptions\ContainerException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $created = $this->scaffold(
            'event',
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
