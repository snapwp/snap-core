<?php

namespace Snap\Core;

class Config
{
    private $path = '';
    private $cache = [];

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
            'supports_featured_images' => [
                'post',
                'page',
            ],
            'reset_image_sizes' => true,
            'image_sizes' => [],
            'insert_image_default_size' => 'medium',
            'insert_image_allow_full_size' => true,
        ],
        'services' => []
    ];

    public function __construct(string $path)
    {
        $this->path = trailingslashit($path);

        $this->load_files();

        $this->parse_files();
    }

    public function get($option, $default = null)
    {
        if ($this->has($option)) {
            return $this->cache[$option];
        }
        return $default;
    }

    public function has($option)
    {
        // Check if already cached
        if (isset($this->cache[$option])) {
            return true;
        }

        $segments = explode('.', $option);
        $root = $this->config;

        // nested case
        foreach ($segments as $segment) {
            if (array_key_exists($segment, $root)) {
                $root = $root[$segment];
                continue;
            } else {
                return false;
            }
        }

        // Set cache for the given key
        $this->cache[$option] = $root;

        return true;
    }

    public function set($option, $value)
    {
        $this->cache[$option] = $value;
    }

    private function load_files()
    {
        if (is_dir($this->path)) {
            $this->files = glob($this->path . '*.*');
        }
    }

    private function parse_files()
    {
        if (!empty($this->files)) {
            foreach ($this->files as $file) {
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

    private function get_filename($path)
    {
        return str_replace('.php', '', basename($path));
    }
}
