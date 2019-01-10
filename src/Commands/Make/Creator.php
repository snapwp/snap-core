<?php

namespace Snap\Commands\Make;

use Snap\Core\Snap;
use Snap\Services\Config;
use Symfony\Component\Console\Command\Command;

/**
 * Base class for all make:* commands.
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
     * @param  array  $args     Any replacement arguments.
     * @param  array  $options  Any replacement options. Used for toggling sections of the scaffold.
     * @return boolean
     *
     * @throws \Hodl\Exceptions\ContainerException
     */
    protected function scaffold($scaffold, $args = [], $options = [])
    {
        $this->init_wordpress();
        Snap::create_container();
        Snap::init_config();
        $original = $this->scaffolding_dir . "{$scaffold}.txt";

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
            $target = $this->get_destination($scaffold, $args);

            // Substitute arguments.
            $content = \str_replace(
                \array_keys($args),
                \array_values($args),
                $content
            );

            if (\file_put_contents($target, $content) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the full path of the new file to be created.
     *
     * @since  1.0.0
     *
     * @param  string $scaffold The file to scaffold.
     * @param  array  $args      The arguments passed from the Maker class.
     * @return string
     */
    protected function get_destination($scaffold, $args)
    {
        $sub_dir = '/theme/';
        $hookables_dir = \trim(Config::get('theme.hookables_directory'), '/');

        switch ($scaffold) {
            case 'shortcode':
                $dir = 'Shortcodes';
                $sub_dir .= \trailingslashit($hookables_dir);
                break;
            case 'ajax':
                $dir = 'Ajax';
                $sub_dir .= 'Http/';
                break;
            case 'hookable':
                $dir = $hookables_dir;
                break;
            case 'controller':
                $dir = 'Controllers';
                $sub_dir .= 'Http/';
                break;
            case 'request':
                $dir = 'Requests';
                $sub_dir .= 'Http/';
                break;
            case 'rule':
                $dir = 'Rules';
                $sub_dir .= 'Http/Validation/';
                break;
            case 'posttype':
                $dir = 'Post_Types';
                $sub_dir .= 'Content/';
                break;
            case 'event':
                $dir = 'Events';
                break;
            case 'taxonomy':
                $dir = 'Taxonomies';
                $sub_dir .= 'Content/';
                break;
        }

        $base_dir = $this->theme_dir . $sub_dir;
        $folder = \str_replace('\\', '/', $args['NAMESPACE']);

        $output_path = $base_dir . '/' . $dir . \trailingslashit($folder);

        \wp_mkdir_p($output_path);

        return $output_path . $args['CLASSNAME']. '.php';
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
     * Ensure a name-spaced $filename can be created as a directory.
     *
     * @since  1.0.0
     *
     * @param  string $filename The (possibly) name-spaced Hookable class name to create.
     * @return string
     */
    private function sanitise_filename($filename)
    {
        return \str_replace('/', '\\', $filename);
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

        $class_name = $this->sanitise_filename($args['CLASSNAME']);

        if ($this->is_nested_directory($class_name)) {
            $parts = \explode('\\', $class_name);
            $class = \array_pop($parts);

            $args['NAMESPACE'] = '\\' . \implode('\\', $parts);
            $args['CLASSNAME'] = $class;
        }

        return $args;
    }

    private function is_nested_directory($class_name)
    {
        return \strpos($class_name, '\\') !== false;
    }
}
