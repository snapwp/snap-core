<?php

namespace Snap\Commands\Make;

use Snap\Commands\Concerns\NeedsWordPress;
use Snap\Core\Snap;
use Snap\Services\Config;
use Snap\Utils\Str;
use Symfony\Component\Console\Command\Command;

/**
 * Base class for all make:* commands.
 */
class Creator extends Command
{
    use NeedsWordPress;

    /**
     * The current working directory.
     *
     * @var string
     */
    protected $theme_dir;

    /**
     * The scaffolding directory.
     *
     * @var string
     */
    protected $scaffolding_dir;

    /**
     * Set properties.
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
     * @param  string $scaffold The file to scaffold.
     * @param  array  $args     Any replacement arguments.
     * @param  array  $options  Any replacement options. Used for toggling sections of the scaffold.
     * @return boolean
     *
     * @throws \Hodl\Exceptions\ContainerException
     */
    protected function scaffold($scaffold, $args = [], $options = [])
    {
        $this->initWordpress();

        Snap::initConfig();

        $original = $this->scaffolding_dir . "{$scaffold}.txt";

        if (\file_exists($original)) {
            $content = \file_get_contents($original);

            if (!empty($options)) {
                foreach ($options as $option => $value) {
                    if ($value !== null) {
                        \preg_match_all("/%IF\|$option%([^%]*)%END%/m", $content, $matches);

                        if (!empty($matches)) {
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
            if (!empty($matches)) {
                foreach ($matches as $match) {
                    if (!empty($match)) {
                        $content = \str_replace($match[0], '', $content);
                    }
                }
            }

            $args = $this->parseArgs($args);
            $target = $this->getDestination($scaffold, $args);

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
     * @param  string $scaffold The file to scaffold.
     * @param  array  $args     The arguments passed from the Maker class.
     * @return string
     */
    protected function getDestination($scaffold, $args)
    {
        $sub_dir = '/theme/';

        switch ($scaffold) {
            case 'shortcode':
                $dir = 'Shortcodes';
                $sub_dir .= 'Content/';
                break;
            case 'ajax':
                $dir = 'Ajax';
                $sub_dir .= 'Http/';
                break;
            case 'hookable':
                $dir = \trim(Config::get('theme.hookables_directory'), '/');
                break;
            case 'controller':
                $dir = 'Controllers';
                $sub_dir .= 'Http/';
                break;
            case 'request':
                $dir = 'Requests';
                $sub_dir .= 'Http/';
                break;
            case 'middleware':
                $dir = 'Middleware';
                $sub_dir .= 'Http/';
                break;
            case 'rule':
                $dir = 'Rules';
                $sub_dir .= 'Http/Validation/';
                break;
            case 'posttype':
                $dir = 'PostTypes';
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

        return $output_path . $args['CLASSNAME'] . '.php';
    }

    /**
     * Ensure a name-spaced $filename can be created as a directory.
     *
     * @param  string $filename The (possibly) name-spaced Hookable class name to create.
     * @return string
     */
    private function sanitiseFilename($filename)
    {
        return \str_replace('/', '\\', $filename);
    }

    /**
     * Populates the NAMESPACE argument based off the passed CLASSNAME.
     *
     * @param  array $args The args passed to the creator.
     * @return array
     */
    private function parseArgs($args = [])
    {
        $args['NAMESPACE'] = '';

        $class_name = $this->sanitiseFilename($args['CLASSNAME']);
        $args['NAME'] = Str::toSnake($class_name);
        $args['PLURAL'] = \ucwords(Str::toPlural(\str_replace('_', ' ', $args['NAME'])));

        if ($this->isNestedDirectory($class_name)) {
            $parts = \explode('\\', $class_name);
            $class = \array_pop($parts);

            $args['NAMESPACE'] = '\\' . \implode('\\', $parts);
            $args['CLASSNAME'] = $class;
            $args['NAME'] = Str::toSnake($class);
            $args['PLURAL'] = \ucwords(Str::toPlural(\str_replace('_', ' ', $args['NAME'])));
        }

        return $args;
    }

    /**
     * Whether the current classname contains a directory.
     *
     * @param string $class_name The classname.
     * @return bool
     */
    private function isNestedDirectory($class_name)
    {
        return \strpos($class_name, '\\') !== false;
    }
}
