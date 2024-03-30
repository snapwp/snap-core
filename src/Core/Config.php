<?php

namespace Snap\Core;

/**
 * A simple config manager.
 */
class Config
{
    /**
     * When a config item is accessed via dot notation, it is stored here for easier retrieval.
     */
    private array $cache = [];

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
            'templates_directory' => 'resources/views',
            'hookables_directory' => 'Hookables/',
            'cache_directory' => 'cache',
            'disable_xmlrpc' => true,
            'disable_comments' => true,
            'disable_widgets_block_editor' => false,
            'disable_customizer' => true,
            'disable_lazy_loading' => false,
        ],
        'assets' => [
            'remove_asset_versions' => true,
            'defer_scripts' => true,
            'defer_scripts_skip' => [],
            'use_jquery_cdn' => '3.2.1',
            'disable_jquery' => false,
            'manifest_path' => '/public/manifest.json'
        ],
        'images' => [
            'default_image_quality' => 75,
            'placeholder_dir' => 'public/images/placeholders/',
            'supports_featured_images' => true,
            'disable_attachment_permalinks' => true,
            'reset_image_sizes' => true,
            'image_sizes' => [],
            'dynamic_image_sizes' => [],
            'insert_image_default_size' => 'medium',
            'insert_image_allow_full_size' => true,
        ],
        'gutenberg' => [
            'disable_custom_colors' => false,
            'disable_custom_font_sizes' => false,
            'disable_typography_features' => [
                'drop_cap' => false,
                'font_style' => false,
                'letter_spacing' => false,
                'text_transform' => false,
            ],
            'disable_block_directory' => false,
            'disable_block_library_css' => false,
            'disable_default_block_styles' => false,
            'simplify_image_size_controls' => false,
            'disable_block_patterns' => false,
            'disabled_block_patterns' => [],
            'enabled_blocks' => [],
            'enable_acf_blocks' => false,
        ],
        'services' => [
            'providers' => [],
            'theme_providers' => [],
            'aliases' => [],
        ],
    ];

    /**
     * Add a new config folder path.
     */
    public function addPath(string $path): void
    {
        $path = \trailingslashit($path);
        $this->parseFiles($path);
    }

    /**
     * Add a new config folder path, but any found config keys will not overwrite an existing entry.
     *
     * New keys are added as per normal.
     */
    public function addDefaultPath(string $path): void
    {
        $path = \trailingslashit($path);
        $this->parseFiles($path, false);
    }

    /**
     * Retrieve a config value.
     */
    public function get(string $option, mixed $default = null): mixed
    {
        if ($this->has($option)) {
            return $this->cache[$option];
        }

        return $default;
    }

    /**
     * Check if a given config value exists in the cache.
     */
    public function has(string $key): bool
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
            }

            return false;
        }

        // Set cache for the given key.
        $this->cache[$key] = $root;

        return true;
    }

    /**
     * Sets an option.
     */
    public function set(string $key, mixed $value): void
    {
        $this->cache[$key] = $value;
    }

    /**
     * Primes and returns the cache array.
     */
    public function getPrimedCache(): array
    {
        $this->recurseThroughConfig($this->config);
        return $this->cache;
    }

    /**
     * Loads config from a cache file.
     */
    public function loadFromCache(string $data): void
    {
        $this->cache = \unserialize($data, false);
    }

    /**
     * Scans a path for config files, and merges them into the config.
     *
     * @param string $path Directory path to scan.
     * @param bool $overwrite If true, then the config within the $path will overwrite existing config keys.
     */
    private function parseFiles(string $path, bool $overwrite = true): void
    {
        if (\is_dir($path)) {
            $files = \glob($path . '*.php');
        }

        if (!empty($files)) {
            foreach ($files as $file) {
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
     */
    private function getFilename(string $path): string
    {
        return \str_replace('.php', '', \basename($path));
    }

    /**
     * Primes the cache array.
     */
    private function recurseThroughConfig(array $values, ?string $path = null): void
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
