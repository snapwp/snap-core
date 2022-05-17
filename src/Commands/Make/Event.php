<?php

namespace Snap\Commands\Make;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class Event extends Creator
{
    /**
     * Setup the command signature and help text.
     */
    protected function configure(): void
    {
        $this->setName('make:event')
            ->setDescription('Creates a new Cron_Event.')
            ->setHelp('Creates a new Cron_Event Hookable within your theme/Events directory');

        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the created Cron_Event.');
    }

    /**
     * Run the command.
     *
     * @throws \Hodl\Exceptions\ContainerException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $created = $this->scaffold(
            'event',
            [
                'CLASSNAME' => $input->getArgument('name'),
            ]
        );

        return $this->writeOutput($created, $input, $output);
    }
}
