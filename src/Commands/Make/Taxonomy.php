<?php

namespace Snap\Commands\Make;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class Taxonomy extends Creator
{
    /**
     * Setup the command signature and help text.
     */
    protected function configure(): void
    {
        $this->setName('make:taxonomy')
            ->setDescription('Creates a new Taxonomy.')
            ->setHelp('Creates a new Taxonomy class within your theme/Content/Taxonomies directory');

        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the created Taxonomy.');
    }

    /**
     * Run the command.
     *
     * @throws \Hodl\Exceptions\ContainerException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $created = $this->scaffold(
            'taxonomy',
            [
                'CLASSNAME' => $input->getArgument('name'),
            ]
        );

        return $this->writeOutput($created, $input, $output);
    }
}
