<?php

namespace Snap\Commands\Make;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class Shortcode extends Creator
{
    /**
     * Setup the command signature and help text.
     */
    protected function configure(): void
    {
        $this->setName('make:shortcode')
            ->setDescription('Creates a new Shortcode.')
            ->setHelp('Creates a new Shortcode class within your theme/Shortcodes directory');

        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the created shortcode.');
    }

    /**
     * Run the command.
     *
     * @throws \Hodl\Exceptions\ContainerException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $created = $this->scaffold(
            'shortcode',
            [
                'CLASSNAME' => $input->getArgument('name'),
            ]
        );

        return $this->writeOutput($created, $input, $output);
    }
}
