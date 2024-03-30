<?php

namespace Snap\Admin;

use Snap\Core\Hookable;
use Snap\Core\Snap;
use Snap\Services\Config;
use WP_Block_Type_Registry;
use WP_Block_Editor_Context;

class Gutenberg extends Hookable
{
    public function boot(): void
    {
        $this->addFilter('enqueue_block_editor_assets', 'enqueueSnapGutenberg');
        $this->addFilter('allowed_block_types_all', 'restrictBlocksByPostType', 99);

        if (Config::get('gutenberg.disable_custom_colors') === true) {
            add_theme_support('editor-color-palette', []);
            add_theme_support('editor-gradient-presets', []);

            add_theme_support('disable-custom-colors');
            add_theme_support('disable-custom-gradients');
        }

        if (Config::get('gutenberg.disable_custom_font_sizes') === true) {
            add_theme_support('editor-font-sizes', []);
            add_theme_support('disable-custom-font-sizes');
        }

        if (Config::get('gutenberg.disable_block_directory') === true) {
            $this->removeAction('enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets');
            $this->removeAction('enqueue_block_editor_assets', 'gutenberg_enqueue_block_editor_assets_block_directory');
        }

        if (Config::get('gutenberg.disable_block_patterns') === true) {
            remove_theme_support('core-block-patterns');
        } else {
            $this->addAction('admin_init', 'filterDefaultBlockPatterns');
        }

        if (Config::get('gutenberg.disable_typography_features.drop_cap') === true) {
            $this->addFilter('block_editor_settings_all', 'disableDropCap');
        }

        if (Config::get('theme.disable_widgets_block_editor') === true) {
            $this->addFilter('use_widgets_block_editor', '__return_false');
        }

        if (Config::get('gutenberg.disable_block_library_css') === true) {
            $this->addAction('wp_enqueue_scripts', 'disableBlockFrontendStyles');
            $this->addAction('wp_footer', 'disableFooterBlockFrontendStyles');
        }

        if (Config::get('gutenberg.simplify_image_size_controls') === true) {
            $this->addAction('admin_head', 'simplifyImageSizeControls');
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
     */
    public function enqueueSnapGutenberg(): void
    {
        $screen = get_current_screen();

        if ($screen && $screen->base === 'widgets') {
            return;
        }

        \wp_enqueue_script(
            'snap-gutenberg',
            \get_theme_file_uri('vendor/snapwp/snap-core/assets/gutenberg.js'),
            ['wp-blocks', 'wp-dom-ready', 'wp-edit-post'],
            Snap::VERSION
        );

        $data = [
            'disableStyles' => Config::get('gutenberg.disable_default_block_styles', false),
        ];

        $data['disabledTypographyFeatures'] = Config::get('gutenberg.disable_typography_features');

        if ($screen->post_type) {
            $data['enabledBlocks'] = Config::get('gutenberg.enabled_blocks')[$screen->post_type] ?? [];
        }

        \wp_localize_script('snap-gutenberg', 'snapGutenbergOptions', $data);
    }

    public function restrictBlocksByPostType(bool|array $allowed_block_types, WP_Block_Editor_Context $block_editor_context): bool|array
    {
        $registry = WP_Block_Type_Registry::get_instance();
        $enabledBlocks = Config::get('gutenberg.enabled_blocks');
        $restrictedBlocks = [];

        if (!$block_editor_context->post) {
            return $allowed_block_types;
        }

        foreach ($enabledBlocks as $postType => $blocks) {
            // continue if the post type does not match the current editor context
            if ($block_editor_context->post->post_type !== $postType) {
                continue;
            }

            if (empty($blocks) || $blocks === true || $blocks === ['*']) {
                return $allowed_block_types;
            }

            foreach ($registry->get_all_registered() as $block => $blockData) {
                // get the simple block matches
                if (in_array($block, $blocks, true)) {
                    $restrictedBlocks[] = $block;
                }

                // get everything before the first forwards lash
                $blockName = explode('/', $block)[0];

                // get the global block matches
                if (in_array($blockName .'/*', $blocks, true)) {
                    $restrictedBlocks[] = $block;
                }
            }
        }

        return $restrictedBlocks;
    }

    /**
     * Disable dropCap post WP 5.6.
     *
     * @param array $editor_settings
     * @return array
     */
    public function disableDropCap(array $editor_settings): array
    {
        $editor_settings['__experimentalFeatures']['typography']['dropCap'] = false;
        return $editor_settings;
    }

    /**
     * Disable the css added to website frontends to style Gutenberg blocks.
     */
    public function disableBlockFrontendStyles(): void
    {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('classic-theme-styles');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('wc-block-style');
        wp_dequeue_style('global-styles');
    }

    /**
     * Disable the footer css added to website frontends to style Gutenberg blocks.
     */
    public function disableFooterBlockFrontendStyles()
    {
        wp_dequeue_style('core-block-supports');
    }

    public function simplifyImageSizeControls(): void
    {
        // todo this is in an iframe now
        echo '
            <style>
                .components-resizable-box__handle,
                .components-resizable-box__container.has-show-handle .components-resizable-box__handle,
                .block-editor-image-size-control {
                    display: none !important;
                }
            </style>
        ';
    }
}
