<?php

namespace Snap\Core;

/**
 * A simple config manager.
 *
 * @since  1.0.0
 */
class Config
{
    /**
     * Config directory paths, in order of addition.
     *
     * @since  1.0.0
     * @var string
     */
    private $paths = '';

    /**
     * When a config item is accessed via dot notation, it is stored here for easier retrieval.
     *
     * @since  1.0.0
     * @var array
     */
    private $cache = [];

    /**
     * Config defaults.
     *
     * @since  1.0.0
     * @var array
     */
    private $config = [
        'theme' => [
            'disable_xmlrpc' => true,
            'disable_comments' => false,
            'remove_asset_versions' => true,
            'defer_scripts' => true,
            'defer_scripts_skip' => [],
            'use_jquery_cdn' => '3.2.1',
            'snap_modules' => [],
        ],
        'images' => [
            'default_image_quality' => 75,
            'placeholder_dir' => 'assets/images/placeholders/',
            'supports_featured_images' => true,
            'reset_image_sizes' => true,
            'image_sizes' => [],
            'insert_image_default_size' => 'medium',
            'insert_image_allow_full_size' => true,
        ],
        'services' => []
    ];

    /**
     * A new config folder path.
     *
     * @since  1.0.0
     *
     * @param string $path Path to config directory.
     */
    public function add_path(string $path)
    {
        $path = trailingslashit($path);
        $this->paths[] = $path;
        $this->parse_files($path);
    }

    /**
     * Retrieve a config value.
     *
     * @since  1.0.0
     *
     * @param  string $option  The config key to retrieve.
     * @param  mixed  $default A default value to return if the config key was not defined.
     * @return mixed The config value.
     */
    public function get($option, $default = null)
    {
        if ($this->has($option)) {
            return $this->cache[$option];
        }

        return $default;
    }

    /**
     * Check if a given config value exists in the cache.
     *
     * @since 1.0.0
     *
     * @param  string $option The dot notation option key to look for
     * @return boolean         Whether an option exists for the given key.
     */
    public function has($key)
    {
        // Check if already cached.
        if (isset($this->cache[$key])) {
            return true;
        }

        $segments = explode('.', $key);
        $root = $this->config;

        foreach ($segments as $segment) {
            if (array_key_exists($segment, $root)) {
                $root = $root[$segment];
                continue;
            } else {
                return false;
            }
        }

        // Set cache for the given key.
        $this->cache[$key] = $root;

        return true;
    }

    /**
     * Sets an option.
     *
     * @since  1.0.0
     *
     * @param string $key   The key of this option.
     * @param mixed  $value The value to set for this option.
     */
    public function set($key, $value)
    {
        $this->cache[$key] = $value;
    }

    /**
     * Scans a path for config files, and merges them into the config.
     *
     * @since  1.0.0
     *
     * @param  string $path Directory path to scan.
     */
    private function parse_files($path)
    {
        if (is_dir($path)) {
            $files = glob($path . '*.php');
        }

        if (! empty($files)) {
            foreach ($files as $file) {
                $parsedOptions = require $file;

                $optionSet = $this->get_filename($file);

                if (!is_array($parsedOptions)) {
                    continue;
                }

                if (isset($this->config[$optionSet])) {
                    $this->config[$optionSet] = array_merge($this->config[$optionSet], $parsedOptions);
                } else {
                    $this->config[$optionSet] = $parsedOptions;
                }
            }
        }
    }

    /**
     * Get the filename without extension from a given path.
     *
     * @since  1.0.0
     *
     * @param  string $path Full file path.
     * @return string       Filename.
     */
    private function get_filename($path)
    {
        return str_replace('.php', '', basename($path));
    }
}
