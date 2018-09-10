<?php

namespace Snap\Modules;

use Snap\Core\Snap;
use Snap\Core\Hookable;
use Snap\Core\Utils;

/**
 * Controls custom image sizes and thumbnail support.
 */
class Images extends Hookable
{
    /**
     * Default WordPress image sizes.
     *
     * @since  1.0.0
     * @var array
     */
    const DEFAULT_IMAGE_SIZES = [ 'thumbnail', 'medium', 'medium_large', 'large' ];

    /**
     * Holds any defined image dropdown names.
     *
     * @since  1.0.0
     * @var array
     */
    public static $size_dropdown_names = [];

    /**
     * The file extensions to check when finding palceholders.
     *
     * @since  1.0.0
     * @var array
     */
    protected $placeholder_extensions = [];

    /**
     * The placeholder directory path.
     *
     * @since  1.0.0
     * @var array
     */
    protected $placeholder_directory = '';

    /**
     * The placeholder directory path URI.
     *
     * @since  1.0.0
     * @var array
     */
    protected $placeholder_directory_uri = '';

    /**
     * The filters to run when booted.
     *
     * @since  1.0.0
     * @var array
     */
    protected $filters = [
        'post_thumbnail_html' => 'placeholder_image_fallback',
        'wp_editor_set_quality' => 'get_upload_quality',
    ];

    /**
     * Register class conditional filters.
     *
     * @since  1.0.0
     */
    public function boot()
    {
        /**
         * The file extensions to search for when looking for placeholder fallback images.
         *
         * @since  1.0.0
         *
         * @param  array $extensions The file extension list, in order of search preference.
         * @return array $extensions The modified file extension list.
         */
        $this->placeholder_extensions = apply_filters('snap_placeholder_img_extensions', [ '.jpg', '.svg', '.png' ]);

        $this->placeholder_directory = trailingslashit(get_stylesheet_directory()) . trailingslashit(Snap::config('images.placeholder_dir'));

        $this->placeholder_directory_uri = trailingslashit(get_stylesheet_directory_uri()) . trailingslashit(Snap::config('images.placeholder_dir'));

        // Enable post-thumbnail support.
        $this->enable_thumbnail_support();

        // Register all image sizes.
        $this->register_image_sizes();

        // Remove all default image sizes.
        if (Snap::config('images.reset_image_sizes') !== false) {
            $this->add_filter('intermediate_image_sizes_advanced', 'remove_default_image_sizes');
            $this->add_filter('intermediate_image_sizes', 'remove_default_image_sizes');
        }

        if (! empty(self::$size_dropdown_names)) {
            $this->add_filter('image_size_names_choose', 'enable_custom_image_sizes');
        }
    
        // Set default image size dropdown value.
        if (! empty(Snap::config('images.insert_image_default_size'))) {
            $this->add_filter('after_setup_theme', 'set_insert_image_default_size');
        }
    }
    
    /**
     * Adds any extra sizes to add media sizes dropdown.
     *
     * @since  1.0.0
     *
     * @param  array $sizes Current sizes for inclusion.
     * @return array Altered $sizes
     */
    public function enable_custom_image_sizes($sizes)
    {
        // Merge custom sizes into $sizes.
        $sizes = \array_merge($sizes, self::$size_dropdown_names);

        // Ensure 'Full size' is always at end.
        unset($sizes['full']);

        if (Snap::config('images.insert_image_allow_full_size') || empty($sizes)) {
            $sizes['full'] = 'Full Size';
        }

        return $sizes;
    }

    /**
     * Returns the image quality option.
     *
     * @since  1.0.0
     *
     * @param  int $quality Existing value.
     * @return int A number between 0-100.
     */
    public function get_upload_quality($quality)
    {
        if (\is_numeric(Snap::config('images.default_image_quality'))) {
            return (int) Snap::config('images.default_image_quality');
        }
        return $quality;
    }

    /**
     * Removes all built in image sizes.
     *
     * @since  1.0.0
     *
     * @param  array $sizes Current registered sizes.
     * @return array Modified $sizes array.
     */
    public function remove_default_image_sizes($sizes = [])
    {
        if (! \is_array(\current($sizes))) {
            return \array_diff($sizes, self::DEFAULT_IMAGE_SIZES);
        }

        return \array_diff_key($sizes, \array_values(self::DEFAULT_IMAGE_SIZES));
    }

    /**
     * Sets the default selected option of the insert image size dropdown.
     *
     * Defaults to medium_large.
     * Also sets default alignment to center.
     *
     * @since  1.0.0
     */
    public function set_insert_image_default_size()
    {
        update_option('image_default_align', 'center');
        update_option('image_default_size', Snap::config('images.insert_image_default_size'));
    }


    /**
     * If no post_thumbnail was found, find the corresponding placeholder image and return the image HTML.
     *
     * @since 1.0.0
     *
     * @param  string       $html              The post thumbnail HTML.
     * @param  int          $post_id           The post ID.
     * @param  string       $post_thumbnail_id The post thumbnail ID.
     * @param  string|array $size              The post thumbnail size. Image size or array of width and height
     *                                         values (in that order). Default 'post-thumbnail'.
     * @param  string       $attr              Query string of attributes.
     * @return string The image HTML
     */
    public function placeholder_image_fallback($html, $post_id, $post_thumbnail_id, $size, $attr)
    {
        if ($html === '' && Snap::config('images.placeholder_dir') !== false) {
            $html = $this->get_placeholder_image($post_id, $post_thumbnail_id, $size, $attr);
        }

        return $html;
    }

    /**
     * Searches for a suitable placeholder fallback image.
     *
     * First checks placeholder-${image_size}, then placeholder-${post_type}, then finally placeholder.
     *
     * Runs through $this->placeholder_extensions in order when searching for placeholders.
     *
     * @since 1.0.0
     *
     * @param  int          $post_id           The post ID.
     * @param  string       $post_thumbnail_id The post thumbnail ID.
     * @param  string|array $size              The post thumbnail size. Image size or array of width and height
     *                                         values (in that order). Default 'post-thumbnail'.
     * @param  string       $attr              Query string of attributes.
     * @return string The image HTML
     */
    public function get_placeholder_image($post_id, $post_thumbnail_id, $size, $attr = [])
    {
        $placeholder_url = false;
        $original_size = $size;

        if (Utils::get_image_size($size) === false) {
            $size = 'full';
        }

        // Search for a size specific placeholder first.
        $placeholder_url = $this->search_for_placeholder('placeholder-' . $size);

        if ($placeholder_url === false) {
            // Then the post type placeholder.
            $placeholder_url = $this->search_for_placeholder('placeholder-' . get_post_type($post_id));
        }

        if ($placeholder_url === false) {
            // Finally a generic placeholder.
            $placeholder_url = $this->search_for_placeholder('placeholder');
        }

        if ($placeholder_url !== false) {
            $html = \sprintf(
                '<img src="%s" alt="%s" width="%d" height="%d" %s>',
                $placeholder_url,
                get_the_title($post_id),
                \is_array($original_size) ? $original_size[0] : Utils::get_image_width($size),
                \is_array($original_size) ? $original_size[1] : Utils::get_image_height($size),
                $this->parse_attributes($attr)
            );

            /**
             * Filter the placeholder image HTML.
             *
             * @since  1.0.0
             *
             * @param string $output The HTML output for the placeholder image tag.
             * @return string $output The HTML output for the placeholder image tag.
             */
            return apply_filters('snap_placeholder_img_html', $html);
        }

        return '';
    }

    /**
     * Scans the file system to see if a given file exists with an extension from $this->placeholder_extensions.
     *
     * @since  1.0.0
     *
     * @param  string $file_name The placeholder to look for, minus extension.
     * @return string|bool false if not found, otherwise the public URI to the found placeholder.
     */
    private function search_for_placeholder($file_name)
    {
        $placeholder_url = false;

        foreach ($this->placeholder_extensions as $ext) {
            // Check if the file exists.
            $file_path = $this->placeholder_directory . $file_name . $ext;
            
            if (\file_exists($file_path) === true) {
                $placeholder_url = $this->placeholder_directory_uri . $file_name . $ext;
                break;
            }
        }

        return $placeholder_url;
    }

    /**
     * Parses image $attr array, turning them into HTML.
     *
     * @since  1.0.0
     *
     * @param  array $attr The $attr array.
     * @return string
     */
    private function parse_attributes($attr)
    {
        $html = '';

        if (! empty($attr)) {
            $html = '';

            foreach ($attr as $key => $value) {
                $html .= \sprintf('%s="%s" ', $key, esc_attr($value));
            }
        }

        return \trim($html);
    }

    /**
     * Enabled theme support for thumbnails.
     *
     * Uses the value of Snap::config( 'images.supports_featured_images' ) enable thumbnails for all post types or a select few.
     *
     * @since  1.0.0
     */
    private function enable_thumbnail_support()
    {
        $enabled_thumbails = Snap::config('images.supports_featured_images');

        if (! empty($enabled_thumbails)) {
            if (\is_array($enabled_thumbails)) {
                add_theme_support('post-thumbnails', $enabled_thumbails);
            } elseif ($enabled_thumbails === true) {
                add_theme_support('post-thumbnails');
            }
        }
    }

    /**
     * Registers image sizes.
     *
     * Also allows easy overwriting of default sizes, as well as the ability to disable them one by one.
     *
     * @since 1.0.0
     */
    private function register_image_sizes()
    {
        if (empty(Snap::config('images.image_sizes'))) {
            return;
        }

        // Loop through sizes.
        foreach (Snap::config('images.image_sizes') as $name => $size_info) {
            // Get size properties with basic fallbacks.
            $width = (int) isset($size_info[0]) ? $size_info[0] : 0;
            $height = (int) isset($size_info[1]) ? $size_info[1] : 0;
            $crop = isset($size_info[2]) ? $size_info[2] : false;

            if (\in_array($name, self::DEFAULT_IMAGE_SIZES)) {
                if ($size_info !== false) {
                    // Set other built-in sizes.
                    update_option($name . '_size_w', $width);
                    update_option($name . '_size_h', $height);
                    update_option($name . '_crop', $crop);
                } else {
                    $callback = function ($sizes = []) use ($name) {
                        return \array_diff($sizes, [ $name ]);
                    };

                    // Remove the size.
                    add_filter('intermediate_image_sizes_advanced', $callback);
                    add_filter('intermediate_image_sizes', $callback);
                }
            } else {
                // Add custom image size.
                add_image_size($name, $width, $height, $crop);
            }

            // If a custom dropdown name has been definaed.
            if (isset($size_info[3]) && ! empty($size_info[3])) {
                self::$size_dropdown_names[ $name ] = $size_info[3];
            }
        }
    }
}
