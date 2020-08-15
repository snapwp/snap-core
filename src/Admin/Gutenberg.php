<?php

namespace Snap\Admin;

use Snap\Core\Hookable;
use Snap\Core\Snap;
use Snap\Services\Config;

/**
 * Enables the Snap admin theme.
 *
 * Based on Slate admin theme.
 *
 * @see   https://wordpress.org/plugins/slate-admin-theme/
 */
class Gutenberg extends Hookable
{
    public function boot(): void
    {
        $this->addFilter('enqueue_block_editor_assets', 'enqueueSnapGutenberg');

        if (Config::get('gutenberg.disable_custom_colors') === true) {
            add_theme_support('editor-color-palette');
            add_theme_support('editor-gradient-presets');

            add_theme_support('disable-custom-colors');
            add_theme_support('disable-custom-gradients');
        }

        if (Config::get('gutenberg.disable_custom_font_sizes') === true) {
            add_theme_support('editor-font-sizes');
            add_theme_support('disable-custom-font-sizes');
        }

        if (Config::get('gutenberg.disable_block_directory') === true) {
            $this->removeAction('enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets');
            $this->removeAction('enqueue_block_editor_assets', 'gutenberg_enqueue_block_editor_assets_block_directory');
        }

        if (Config::get('gutenberg.disable_block_patterns') === true) {
            remove_theme_support('core-block-patterns');
        } else {
            $this->addAction('admin_head', 'filterDefaultBlockPatterns');
        }
    }

    /**
     * Filter registered block patterns.
     */
    public function filterDefaultBlockPatterns(): void
    {
        collect(Config::get('gutenberg.disabled_block_patterns'))
            ->each('unregister_block_pattern');
    }

    /**
     * Enqueue Snap Gutenberg js helper and localize.
     *
     * The disable_drop_cap feature is temporary until the block_editor_features hook or theme.json become integrated
     * into core.
     *
     * @url https://make.wordpress.org/core/2020/01/23/controlling-the-block-editor/
     * @url https://developer.wordpress.org/block-editor/developers/themes/theme-json/
     *
     * The disabled_blocks feature is also temporary until the below github issue is resolved.
     *
     * @url https://github.com/WordPress/gutenberg/issues/12484
     */
    public function enqueueSnapGutenberg(): void
    {
        \wp_enqueue_script(
            'snap-gutenberg',
            \get_theme_file_uri('vendor/snapwp/snap-core/assets/gutenberg.js'),
            ['wp-blocks', 'wp-dom-ready', 'wp-edit-post'],
            Snap::VERSION
        );

        $data = [
            'disableDropCaps' => false,
            'disabledBlocks' => Config::get('gutenberg.disabled_blocks', []),
        ];

        if (Config::get('gutenberg.disable_drop_cap') === true) {
            $data['disableDropCaps'] = true;
        }

        \wp_localize_script('snap-gutenberg', 'snapGutenbergOptions', $data);
    }
}
