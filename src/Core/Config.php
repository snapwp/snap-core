<?php

namespace Snap\Core;

/**
 * A simple config manager.
 */
class Config
{
    /**
     * Config directory paths, in order of addition.
     *
     * @var string
     */
    private $paths = [];

    /**
     * When a config item is accessed via dot notation, it is stored here for easier retrieval.
     *
     * @var array
     */
    private $cache = [];

    /**
     * Config defaults.
     *
     * @var array
     */
    private $config = [
        'admin' => [
            'snap_admin_theme' => false,
            'footer_text' => false,
            'show_version' => true,
            'login_extra_css' => false,
            'login_logo_url' => '/',
        ],
        'theme' => [
            'templates_directory' => 'resources/templates',
            'hookables_directory' => 'Hookables/',
            'cache_directory' => 'cache',
            'disable_xmlrpc' => true,
            'disable_comments' => true,
            'disable_customizer' => true,
            'remove_asset_versions' => true,
            'defer_scripts' => true,
            'defer_scripts_skip' => [],
            'use_jquery_cdn' => '3.2.1',
            'disable_jquery' => false,
        ],
        'images' => [
            'default_image_quality' => 75,
            'placeholder_dir' => 'public/images/placeholders/',
            'supports_featured_images' => true,
            'reset_image_sizes' => true,
            'image_sizes' => [],
            'dynamic_image_sizes' => [],
            'insert_image_default_size' => 'medium',
            'insert_image_allow_full_size' => true,
        ],
        'services' => [
            'providers' => [],
            'theme_providers' => [\Theme\ThemeProvider::class],
            'aliases' => [],
        ],
    ];

    /**
     * Add a new config folder path.
     *
     * @param string $path Path to config directory.
     */
    public function addPath(string $path)
    {
        $path = \trailingslashit($path);
        $this->paths[] = $path;
        $this->parseFiles($path);
    }

    /**
     * Add a new config folder path, but any found config keys will not overwrite an existing entry.
     *
     * New keys are added as per normal.
     *
     * @param string $path Path to config directory.
     */
    public function addDefaultPath(string $path)
    {
        $path = \trailingslashit($path);
        $this->paths[] = $path;
        $this->parseFiles($path, false);
    }

    /**
     * Retrieve a config value.
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
     * @param  string $key The dot notation option key to look for.
     * @return boolean Whether an option exists for the given key.
     */
    public function has($key)
    {
        // Check if already cached.
        if (isset($this->cache[$key])) {
            return true;
        }

        $segments = \explode('.', $key);
        $root = $this->config;

        foreach ($segments as $segment) {
            if (\array_key_exists($segment, $root)) {
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
     * @param string $key   The key of this option.
     * @param mixed  $value The value to set for this option.
     */
    public function set($key, $value)
    {
        $this->cache[$key] = $value;
    }

    /**
     * Primes and returns the cache array.
     *
     * @return array
     */
    public function getPrimedCache(): array
    {
        $this->recurseThroughConfig($this->config);
        return $this->cache;
    }

    /**
     * Loads config from a cache file.
     *
     * @param string $data Serialized config array.
     */
    public function loadFromCache($data)
    {
        $this->cache = \unserialize($data);
    }


    /**
     * Scans a path for config files, and merges them into the config.
     *
     * @param string $path      Directory path to scan.
     * @param bool   $overwrite If true, then the config within the $path will overwrite existing config keys.
     */
    private function parseFiles($path, $overwrite = true)
    {
        if (\is_dir($path)) {
            $files = \glob($path . '*.php');
        }

        if (!empty($files)) {
            foreach ($files as $file) {
                /** @noinspection PhpIncludeInspection */
                $parsed_options = require $file;

                $option_set = $this->getFilename($file);

                if (!\is_array($parsed_options)) {
                    continue;
                }

                if (isset($this->config[$option_set])) {
                    if ($overwrite) {
                        $this->config[$option_set] = \array_merge($this->config[$option_set], $parsed_options);
                    } else {
                        $this->config[$option_set] = \array_merge($parsed_options, $this->config[$option_set]);
                    }
                } else {
                    $this->config[$option_set] = $parsed_options;
                }
            }
        }
    }

    /**
     * Get the filename without extension from a given path.
     *
     * @param  string $path Full file path.
     * @return string Filename.
     */
    private function getFilename(string $path): string
    {
        return \str_replace('.php', '', \basename($path));
    }

    /**
     * Primes the cache array.
     *
     * @param array  $values An array to extract the keys from.
     * @param string $path   The current level's key.
     */
    private function recurseThroughConfig(array $values, $path = null)
    {
        if (empty($values)) {
            $this->has($path);
        }

        foreach ($values as $key => $value) {
            if (!\is_string($key)) {
                $this->has($path);
                continue;
            }

            if (\is_array($value)) {
                $this->recurseThroughConfig($value, $path ? "$path.$key" : $key);
            } else {
                $this->has("$path.$key");
            }
        }
    }
}
