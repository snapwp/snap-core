<?php

namespace Snap\Commands;

use Symfony\Component\Console\Command\Command;

class Creator extends Command
{
    /**
     * The current working directory.
     *
     * @since  1.0.0
     * @var string
     */
    protected $theme_dir;

    /**
     * The scaffolding directory.
     *
     * @since  1.0.0
     * @var string
     */
    protected $scaffolding_dir;

    /**
     * Set properties.
     *
     * @since  1.0.0
     */
    public function __construct()
    {
        $this->theme_dir = \getcwd();
        $this->scaffolding_dir = __DIR__ . '/scaffolding/';

        parent::__construct();
    }

    /**
     * Update a scaffold file and put into the active theme.
     *
     * @since  1.0.0
     *
     * @param  string $scaffold The file to scaffold.
     * @param  string $filename The name of the new file to be generated.
     * @param  array  $args     Any replacement args.
     * @return boolean
     */
    protected function scaffold($scaffold, $filename, $args)
    {
        $original = $this->scaffolding_dir . "{$scaffold}.php";

        if (\file_exists($original)) {
            $content = \file_get_contents($original);

            $content = \str_replace(
                \array_keys($args),
                \array_values($args),
                $content
            );
            
            if (\file_put_contents($this->get_destination($scaffold, $filename), $content) !== false) {
                return true;
            }

            return false;
        }
    }

    /**
     * Ensures the target directory for the new file exists, and creates if not.
     *
     * @since  1.0.0
     *
     * @param  string $dir The directory to check.
     */
    protected function create_destination_dir($dir)
    {
        $theme_dir = "{$this->theme_dir}/theme";

        if (! \is_dir($theme_dir)) {
            \mkdir($theme_dir, 0755);
        }

        if (! \is_dir($theme_dir . '/' . $dir)) {
            \mkdir($theme_dir . '/' . $dir, 0755);
        }
    }

    /**
     * Returns the full path of the new file to be created.
     *
     * @since  1.0.0
     *
     * @param  string $scaffold The file to scaffold.
     * @param  string $filename The name of the new file to be generated.
     * @return string
     */
    protected function get_destination($scaffold, $filename)
    {
        switch ($scaffold) {
            case 'shortcode':
                $dir = 'Shortcodes';
                break;
            case 'ajax':
                $dir = 'Ajax';
                break;
            case 'hookable':
                $dir = 'Hookables';
                break;
            case 'controller':
                $dir = 'Controllers';
                break;
            case 'posttype':
                $dir = 'Posts';
                break;
            case 'taxonomy':
                $dir = 'Taxonomies';
                break;
        }

        $this->create_destination_dir($dir);

        return $this->theme_dir . '/theme/' . $dir . '/' .$filename . '.php';
    }
}
