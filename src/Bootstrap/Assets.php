<?php

namespace Snap\Bootstrap;

use Snap\Core\Config;
use Snap\Core\Hookable;

/**
 * All asset (script and style) related functionality.
 */
class Assets extends Hookable
{
    /**
     * Don't run on admin requests.
     *
     * @var boolean
     */
    protected $admin = false;

    /**
     * Actions to add on init.
     *
     * @var array
     */
    protected $actions = [
        'wp_enqueue_scripts' => 'enqueueScripts',
    ];

    /**
     * @var \Snap\Core\Config
     */
    private $config;

    /**
     * Inject the config instance.
     *
     * @param \Snap\Core\Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Adds optional filters if required.
     */
    public function boot()
    {
        // Whether to add 'defer' to enqueued scripts.
        if ($this->config->get('assets.defer_scripts')) {
            $this->addFilter('script_loader_tag', 'deferScripts', 10, 2);
        }

        // Whether to remove asset version strings.
        if ($this->config->get('assets.remove_asset_versions')) {
            $this->addFilter('style_loader_src', 'removeVersionsFromAssets', 15);
            $this->addFilter('script_loader_src', 'removeVersionsFromAssets', 15);
        }
    }

    /**
     * Optionally replace the default WordPress jQuery with a Google CDN version
     * and enqueue the child theme assets.
     */
    public function enqueueScripts()
    {
        // Get specified jQuery version.
        $jquery_version = $this->config->get('assets.use_jquery_cdn');

        // if a valid jQuery version has been specified.
        if ($jquery_version !== false
            && $this->config->get('assets.disable_jquery') !== true
            && \version_compare($jquery_version, '0.0.1', '>=') === true
        ) {
            // get all non-deferred scripts, to check for jQuery.
            $defer_exclude_list = $this->config->get('assets.defer_scripts_skip');

            \wp_deregister_script('jquery');

            \wp_register_script(
                'jquery',
                "//ajax.googleapis.com/ajax/libs/jquery/{$jquery_version}/jquery.min.js",
                [],
                null,
                (\is_array($defer_exclude_list) && \in_array('jquery', $defer_exclude_list)) ? false : true
            );

            \wp_enqueue_script('jquery');
        }

        // Completely remove jQuery
        if ($this->config->get('assets.disable_jquery') === true) {
            \wp_deregister_script('jquery');
        }

        // Threaded comments JS.
        if ((! $this->config->get('theme.disable_comments'))
            && \comments_open()
            && \get_option('thread_comments')
        ) {
            \wp_enqueue_script('comment-reply');
        }
    }

    /**
     * If enabled, adds defer attribute to js scripts.
     *
     * @param string $tag    HTML of current script.
     * @param string $handle Handle of current script.
     * @return string HTML output for this script.
     */
    public function deferScripts($tag, $handle)
    {
        $excludes = $this->config->get('assets.defer_scripts_skip');

        // Get the script handles to exclude.
        if (empty($excludes)) {
            $exclude_list = [];
        } else {
            $exclude_list = $excludes;
        }

        // If the defer_scripts_skip option was not present, or was incompatible.
        if (! \is_array($exclude_list)) {
            $exclude_list = [];
        }

        if (\in_array($handle, $exclude_list)) {
            return $tag;
        }

        return \str_replace(' src', ' defer="defer" src', $tag);
    }

    /**
     * Remove version query string from all styles and scripts.
     *
     * @param string $src The src URL for the asset.
     * @return string The URL without an asset string.
     */
    public function removeVersionsFromAssets($src)
    {
        return $src ? \esc_url(\remove_query_arg('ver', $src)) : false;
    }
}
