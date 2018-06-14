<?php

namespace Snap\Core\Commands;

use Symfony\Component\Console\Command\Command;

class Creator extends Command
{
    /**
     * The current working directory.
     *
     * @since  1.0.0
     * @var string
     */
    protected $themeDir;

    /**
     * The scaffolding directory.
     *
     * @since  1.0.0
     * @var string
     */
    protected $scaffoldingDir;

    /**
     * Set properties.
     *
     * @since  1.0.0
     */
    public function __construct()
    {
        $this->themeDir = \getcwd();
        $this->scaffoldingDir = __DIR__ . '/scaffolding/';

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
        $original = $this->scaffoldingDir . "{$scaffold}.php";

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
        $themeDir = "{$this->themeDir}/theme";

        if (! \is_dir($themeDir)) {
            \mkdir($themeDir, 0755);
        }

        if (! \is_dir($themeDir . '/' . $dir)) {
            \mkdir($themeDir . '/' . $dir, 0755);
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
            case 'hookable':
                $dir = 'Hookables';
                break;
        }

        $this->create_destination_dir($dir);

        return $this->themeDir . '/theme/' . $dir . '/' .$filename . '.php';
    }
}
