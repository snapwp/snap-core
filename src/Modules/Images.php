<?php

namespace Snap\Core\Modules;

use Snap\Core\Snap;
use Snap\Core\Hookable;
use Snap\Core\Config;

/**
 * Controls custom image sizes and thumbnail support.
 */
class Images extends Hookable
{
    /**
     * Default WordPress image sizes.
     *
     * @since  1.0.0
     *
     * @var array
     */
    const DEFAULT_IMAGE_SIZES = [ 'thumbnail', 'medium', 'medium_large', 'large' ];

    /**
     * Holds any defined image dropdown names.
     *
     * @since  1.0.0
     *
     * @var array
     */
    public static $size_dropdown_names = [];

    /**
     * Boot up and get image related config.
     *
     * @since  1.0.0
     * 
     * @param Config $config Config service.
     */
    public function __construct(Config $config)
    {
        $this->config = $config->get('images');
    }

    /**
     * Register class conditional filters.
     *
     * @since  1.0.0
     */
    public function boot()
    {
        // Override the default image compression quality.
        if (is_numeric($this->config['default_image_quality'])) {
            $this->add_filter('wp_editor_set_quality', 'get_upload_quality');
        }

        // Enable post-thumbnail support.
        $this->enable_thumbnail_support();

        // Remove all default image sizes.
        if ($this->config['reset_image_sizes'] !== false) {
            $this->add_filter('intermediate_image_sizes_advanced', 'remove_default_image_sizes');
            $this->add_filter('intermediate_image_sizes', 'remove_default_image_sizes');
        }

        // Add custom image sizes if defined.
        if (! empty($this->config['image_sizes'])) {
            // Register all image sizes.
            $this->register_image_sizes();

            if (! empty(self::$size_dropdown_names)) {
                $this->add_filter('image_size_names_choose', 'enable_custom_image_sizes');
            }
        }

        // Set default image size dropdown value.
        if (! empty($this->config['insert_image_default_size'])) {
            $this->add_filter('after_setup_theme', 'set_insert_image_default_size');
        }
    }
    
    /**
     * Adds any extra sizes to add media sizes dropdown.
     *
     * @since  1.0.0
     *
     * @param  array $sizes Current sizes for inclusion.
     * @return array        Altered $sizes
     */
    public function enable_custom_image_sizes($sizes)
    {
        // Merge custom sizes into $sizes.
        $sizes = array_merge($sizes, self::$size_dropdown_names);

        // Ensure 'Full size' is always at end.
        unset($sizes['full']);

        if ($this->config['insert_image_allow_full_size'] || empty($sizes)) {
            $sizes['full'] = 'Full Size';
        }

        return $sizes;
    }

    /**
     * Returns the image quality option.
     *
     * @since  1.0.0
     *
     * @return  int A number between 0-100.
     */
    public function get_upload_quality()
    {
        return (int) $this->config['default_image_quality'];
    }

    /**
     * Removes all built in image sizes.
     *
     * @since  1.0.0
     *
     * @param  array $sizes Current registered sizes.
     * @return array        Modified $sizes array.
     */
    public function remove_default_image_sizes($sizes = [])
    {
        return array_diff($sizes, self::DEFAULT_IMAGE_SIZES);
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
        update_option('image_default_size', $this->config['insert_image_default_size']);
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
        $enabled_thumbails = $this->config['supports_featured_images'];

        if (! empty($enabled_thumbails)) {
            if (is_array($enabled_thumbails)) {
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
        // Loop through sizes.
        foreach ($this->config['image_sizes'] as $name => $size_info) {
            // Get size properties with basic fallbacks.
            $width = (int) isset($size_info[0]) ? $size_info[0] : 0;
            $height = (int) isset($size_info[1]) ? $size_info[1] : 0;
            $crop = isset($size_info[2]) ? $size_info[2] : false;

            if (in_array($name, self::DEFAULT_IMAGE_SIZES)) {
                if ($size_info !== false) {
                    // Set other built-in sizes.
                    update_option($name . '_size_w', $width);
                    update_option($name . '_size_h', $height);
                    update_option($name . '_crop', $crop);
                } else {
                    $callback = function ($sizes = []) use ($name) {
                        return array_diff($sizes, [ $name ]);
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
