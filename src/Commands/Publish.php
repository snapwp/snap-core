<?php

namespace Snap\Commands;

use Snap\Core\Snap;
use Snap\Services\Config;
use Snap\Services\Service_Provider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Creates an Ajax class in the current directory.
 */
class Publish extends Command
{
    /**
     * Store the Command Helper instance.
     *
     * @since  1.0.0
     * @var \Symfony\Component\Console\Helper\QuestionHelper
     */
    private $helper;

    /**
     * All copied file paths.
     *
     * @since  1.0.0
     * @var array
     */
    private $copied = [];

    /**
     * Whether the force flag has been set.
     *
     * @since 1.0.0
     * @var boolean
     */
    private $force = false;

    /**
     * Used instead of get_template_directory if present.
     *
     * @since 1.0.0
     * @var boolean
     */
    private $root;

    /**
     * The input interface.
     *
     * @since 1.0.0
     * @var InputInterface
     */
    private $input;

    /**
     * The output interface.
     *
     * @since 1.0.0
     * @var OutputInterface
     */
    private $output;

    /**
     * The wp filesystem class.
     *
     * @since 1.0.0
     * @var \WP_Filesystem_Direct
     */
    private $file;

    /**
     * Setup the command signature and help text.
     *
     * @since  1.0.0
     */
    protected function configure()
    {
        $this->setName('publish')
            ->setDescription('Publishes files from a package into the current SnapWP theme.')
            ->setHelp('Publishes files from a package into the current SnapWP theme.');

        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Overwrite any published files if they already exist.'
        );

        $this->addOption(
            'all',
            'a',
            InputOption::VALUE_NONE,
            'Publish files from all packages.'
        );

        $this->addOption(
            'package',
            null,
            InputOption::VALUE_REQUIRED,
            'Chose the specific package to publish.'
        );

        $this->addOption(
            'root',
            'r',
            InputOption::VALUE_OPTIONAL,
            'To override the current theme directory.'
        );
    }

    /**
     * Run the command.
     *
     * @since  1.0.0
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface $output Command output.
     *
     * @throws \Hodl\Exceptions\ContainerException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init($input, $output);

        $this->force_prompt();
                
        $this->publish_packages($this->get_packages());
        
        // Feedback how many files were copied.
        if (\count($this->copied)) {
            $this->output->writeln(
                \sprintf(
                    "\n<info>Published %d %s successfully:\n%s</info>",
                    \count($this->copied),
                    _n('file', 'files', \count($this->copied)),
                    \implode($this->copied, "\n")
                )
            );
            exit;
        }
        
        $this->output->writeln("\n<info>Nothing needed to be published</info>");
    }

    /**
     * Set up class properties and initialise Snap.
     *
     * @since 1.0.0
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface $output Command output.
     *
     * @throws \Hodl\Exceptions\ContainerException
     */
    private function init(InputInterface $input, OutputInterface $output)
    {
        $this->helper = $this->getHelper('question');
        $this->input = $input;
        $this->output = $output;

        // Set whether target files should be overwritten or not.
        $this->force = $input->getOption('force');
        $this->root = $input->getOption('root');

        $this->init_wordpress();

        // Setup Snap.
        Snap::create_container();
        Snap::init_config($this->root);

        // Setup WP filesystem helper.
        $this->setup_filesystem();
    }

    /**
     * Gets a list of packages and their files to publish.
     *
     * @since  1.0.0
     *
     * @return array
     */
    private function get_packages()
    {
        if ($this->input->getOption('all') === true) {
            $packages = [];
            
            // Add all package files to $packages array.
            foreach (Config::get('services.providers') as $package) {
                $packages[ $package ] = $package::get_files_to_publish();
            }

            return $packages;
        }

        $package = $this->input->getOption('package');

        // If no package specified.
        if ($package === null) {
            $package = $this->package_prompt();
        }

        // If the package chosen does not exist.
        if (! \class_exists($package)) {
            $this->output->writeln("<error>The package [$package] could not be found.</error>");
            exit;
        }

        // When running publish via the theme installer, the current theme isn't active yet.
        //  We need to trick WP into thinking it is.
        if (isset($this->root)) {
            $parts = \explode('/', $this->root);

            if ($parts[0] == $this->root) {
                $parts = \explode('\\', $this->root);
            }

            $theme_name = \end($parts);

            \add_filter(
                'stylesheet',
                function () use ($theme_name) {
                    return $theme_name;
                }
            );
            \add_filter(
                'template',
                function () use ($theme_name) {
                    return $theme_name;
                }
            );

            Snap::register_providers();
        }

        return [
            $package => $package::get_files_to_publish(),
        ];
    }

    /**
     * Loop through all user selected packages, and publish the package files.
     *
     * @since  1.0.0
     *
     * @param  array $packages Array of package provider => files to publish.
     */
    private function publish_packages($packages)
    {
        if (empty($packages)) {
            return;
        }

        foreach ($packages as $package => $directories_to_publish) {
            if (empty($directories_to_publish)) {
                $this->output->writeln("<comment>$package has no files to publish!</comment>");
                continue;
            }

            $this->publish_directories($directories_to_publish);
        }
    }

    /**
     * Loop through all directories to be published by the selected package, and attempt to create them.
     *
     * @since  1.0.0
     *
     * @param  array $directories List of all directories registered by the package to publish.
     */
    private function publish_directories($directories)
    {
        $root = \get_template_directory();

        if (\is_child_theme()) {
            $root = \get_stylesheet_directory();
        }

        if (isset($this->root)) {
            $root = $this->root;
        }

        foreach ($directories as $source => $target) {
            $target_path = \trailingslashit($root) . \ltrim($target, '/\\');

            // Check if source exists.
            if (! \is_dir($source)) {
                $this->output->writeln("<error>The package source [$source] could not be found or is not readable. Skipping.</error>");
                continue;
            }

            // Check if target exists yet, create if not.
            if (! \is_dir($target_path)) {
                $this->mkdir($target_path);
            }

            // Now we know it exists, check we can write to it.
            if (! \is_writable($target_path)) {
                $this->output->writeln("<error>[$target_path] is not writable. Please check directory permissions.</error>");
                continue;
            }
            
            $this->deep_copy($source, $target_path);
        }
    }

    /**
     * Recursively copy $source directory to $target_path.
     *
     * @since  1.0.0
     *
     * @param  string $source      The source directory path.
     * @param  string $target_path The target directory path.
     */
    private function deep_copy($source, $target_path)
    {
        // Loop through $source dir.
        $contents = \scandir($source);

        if (empty($contents)) {
            return;
        }

        foreach ($contents as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }

            $src_path = \trailingslashit($source) . $file;
            $new_path = \trailingslashit($target_path) . $file;

            if (\is_dir($src_path)) {
                $this->mkdir($new_path);
                $this->deep_copy($src_path, $new_path);
                continue;
            }

            if ($this->file->copy($src_path, $new_path, $this->force)) {
                $this->copied[] = $new_path;
            }
        }
    }

    /**
     * Attempt to create the target directory.
     *
     * @since  1.0.0
     *
     * @param  string $target The directory path to create.
     */
    private function mkdir($target)
    {
        if (\is_dir($target)) {
            return;
        }

        if (! $this->file->mkdir($target)) {
            $this->output->writeln("<error>[$target] could not be created. Please check directory permissions.</error>");
        }
    }

    /**
     * Setup the WP_Filesystem_Direct instance.
     *
     * @since  1.0.0
     */
    private function setup_filesystem()
    {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        \WP_Filesystem();
        global $wp_filesystem;
        $this->file = $wp_filesystem;
    }

    /**
     * Include and boot up WordPress.
     *
     * @since  1.0.0
     */
    private function init_wordpress()
    {
        global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;


        // Trick WP into thinking this is an AJAX request. Helps quieten certain plugins.
        \define('DOING_AJAX', true);

        \define('BASE_PATH', $this->find_wordpress_base_path());
        \define('WP_USE_THEMES', false);
        require(BASE_PATH . 'wp-load.php');
    }

    /**
     * Traverse up the directory structure looking for the current WP base path.
     *
     * @since  1.0.0
     *
     * @return string The base path.
     */
    private function find_wordpress_base_path()
    {
        $dir = \dirname(__FILE__);

        do {
            if (\file_exists($dir . "/wp-config.php") || \file_exists($dir . "/wp-config-sample.php")) {
                return $dir . '/';
            }
        } while ($dir = \realpath("$dir/.."));

        return null;
    }

    /**
     * Ask if the user wishes to continue with the force flag enabled.
     *
     * @since  1.0.0
     */
    private function force_prompt()
    {
        if ($this->force === false) {
            return;
        }

        $question = new ConfirmationQuestion(
            "\n<error>Force flag enabled. Any published files will overwrite target files if they exist.</error> \n\nContinue? ",
            false
        );

        if (! $this->helper->ask($this->input, $this->output, $question)) {
            exit;
        }
    }

    /**
     * Prompts the user to pick a package from the list.
     *
     * @since  1.0.0
     *
     * @return  string The chosen package provider.
     */
    private function package_prompt()
    {
        $question = new ChoiceQuestion(
            "\nPlease choose a package to publish:",
            Config::get('services.providers')
        );

        $question->setErrorMessage('[%s] is invalid.');

        return $this->helper->ask($this->input, $this->output, $question);
    }
}
