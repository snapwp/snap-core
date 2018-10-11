<?php

namespace Snap\Commands\Make;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Creates a shortcode class in the current directory.
 *
 * @since  1.0.0
 */
class Shortcode extends Creator
{
    /**
     * Setup the command signature and help text.
     *
     * @since  1.0.0
     */
    protected function configure()
    {
        $this->setName('make:shortcode')
            ->setDescription('Creates a new Shortcode.')
            ->setHelp('Creates a new Shortcode class within your theme/Shortcodes directory');

        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the created shortcode.');
    }

   /**
    * Run the command.
    *
    * @since  1.0.0
    *
    * @param  InputInterface  $input Command input.
    * @param  OutputInterface $output Command output.
    */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $created = $this->scaffold(
            'shortcode',
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
