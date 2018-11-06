<?php

namespace Snap\Commands\Make;

use Symfony\Component\Console\Command\Command;

/**
 * Case class for all make:* commands.
 */
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
     * @param  array  $args     Any replacement arguments.
     * @param  array  $options  Any replacement options. Used for toggling sections of the scaffold.
     * @return boolean
     */
    protected function scaffold($scaffold, $filename, $args = [], $options = [])
    {
        $this->init_wordpress();
        $original = $this->scaffolding_dir . "{$scaffold}.txt";

        $filename = $this->sanitise_filename($filename);

        if (\file_exists($original)) {
            $content = \file_get_contents($original);

            if (! empty($options)) {
                foreach ($options as $option => $value) {
                    if ($value !== null) {
                        \preg_match_all("/%IF\|$option%([^%]*)%END%/m", $content, $matches);

                        if (! empty($matches)) {
                            $content = \str_replace(
                                $matches[0],
                                \str_replace($option, $value, $matches[1]),
                                $content
                            );
                        }
                    }
                }
            }

            \preg_match_all("/%IF[^%]*%([^%]*)%END%/m", $content, $matches);

            // Clean up any IF tags.
            if (! empty($matches)) {
                foreach ($matches as $match) {
                    if (! empty($match)) {
                        $content = \str_replace($match[0], '', $content);
                    }
                }
            }

            $args = $this->parse_args($args);

            // Substitute arguments.
            $content = \str_replace(
                \array_keys($args),
                \array_values($args),
                $content
            );

            if (\file_put_contents($this->get_destination($scaffold, $filename), $content) !== false) {
                return true;
            }
        }

        return false;
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

        wp_mkdir_p($theme_dir . '/' . $dir);
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

        \preg_match('/(.*)\/[^\/]*$/', $dir . '/' . $filename, $match);

        if (isset($match[1])) {
            $this->create_destination_dir($match[1]);
        } else {
            $this->create_destination_dir($dir);
        }

        return $this->theme_dir . '/theme/' . $dir . '/' . $filename . '.php';
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
        \define('SHORTINIT', true);

        \define('BASE_PATH', $this->find_wordpress_base_path());
        \define('WP_USE_THEMES', false);
        require(BASE_PATH . 'wp-load.php');
    }

    /**
     * Traverse up the cirectory structure looking for the current WP base path.
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
     * Ensure a namepsaced $filename can be created as a directory.
     *
     * @since  1.0.0
     *
     * @param  string $filename The (poissbly) namespaced Hookable class name to create.
     * @return string
     */
    private function sanitise_filename($filename)
    {
        return \str_replace('\\', '/', $filename);
    }

    /**
     * Populates the NAMESPACE argument based off the passed CLASSNAME.
     *
     * @since  1.0.0
     *
     * @param  array $args The args passed to the creator.
     * @return array
     */
    private function parse_args($args = [])
    {
        $args['NAMESPACE'] = '';

        $classname = $this->sanitise_filename($args['CLASSNAME']);

        if (\strpos($classname, '/') !== false) {
            \preg_match('/(.*)\/[^\/]*$/', $classname, $match);

            $args['NAMESPACE'] = '\\' . $match[1];
            $args['CLASSNAME'] = \end(\explode('/', $args['CLASSNAME']));
        }

        return $args;
    }
}
