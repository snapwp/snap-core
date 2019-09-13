<?php

namespace Snap\Media;

use Snap\Core\Hookable;
use Snap\Services\Config;

/**
 * Controls custom image sizes and thumbnail support.
 */
class SizeManager extends Hookable
{
    /**
     * Default WordPress image sizes.
     *
     * @since  1.0.0
     * @var array
     */
    const DEFAULT_IMAGE_SIZES = [
        'thumbnail',
        'medium',
        'medium_large',
        'large',
    ];
    /**
     * Holds any defined image sizes intended for theme use only.
     *
     * @since  1.0.0
     * @var array
     */
    private static $dynamic_sizes = [];

    /**
     * Holds any defined image dropdown names.
     *
     * @since  1.0.0
     * @var array
     */
    public $size_dropdown_names = [];

    /**
     * @var Image_Service
     */
    private $image_service;

    /**
     * The filters to run when booted.
     *
     * @since  1.0.0
     * @var array
     */
    protected $filters = [
        'post_thumbnail_html' => 'placeholderImageFallback',
        'wp_editor_set_quality' => 'getUploadQuality',
        'intermediate_image_sizes_advanced' => 'removeCustomImageSizes',
        'max_srcset_image_width' => 'max_srcset_image_width',
        'wp_calculate_image_sizes' => 'update_max_size_attr_in_srcset_size_attr',
    ];

    /**
     * Inject Image_Service
     *
     * @param Image_Service $image_service
     */
    public function __construct(Image_Service $image_service)
    {
        $this->image_service = $image_service;
        $this->setDynamicSizes();
    }

    /**
     * Register class conditional filters.
     *
     * @since  1.0.0
     */
    public function boot()
    {
        // Enable post-thumbnail support.
        $this->enableThumbnailSupport();

        // Register all image sizes.
        $this->registerImageSizes();

        if (! empty($this->size_dropdown_names)) {
            $this->addFilter('image_size_names_choose', 'enableCustomImageSizes');
        }

        // Set default image size dropdown value.
        if (! empty(Config::get('images.insert_image_default_size'))) {
            $this->addFilter('after_setup_theme', 'setInsertImageDefaultSize');
        }

        if (Config::get('images.dynamic_image_sizes') !== false) {
            $this->addFilter('image_downsize', 'generateDynamicImage');
        }
    }

    /**
     * Return the theme dynamic image sizes.
     *
     * @since  1.0.0
     *
     * @return array
     */
    public static function getDynamicSizes()
    {
        return \array_keys(self::$dynamic_sizes);
    }
    
    public function update_max_size_attr_in_srcset_size_attr($sizes, $size, $image_src, $image_meta)
    {
        $biggest = $size[0];

        if (isset($image_meta['sizes']) && !empty($image_meta['sizes'])) {
            foreach ($image_meta['sizes'] as $key => $s) {
                if (!isset($s['width'])) {
                    continue;
                }

                if (\wp_image_matches_ratio($size[0], $size[1], $s['width'], $s['height'])) {
                    if ($s['width'] > $biggest) {
                        $biggest = $s['width'];
                    }
                }
            }
        }

        if ($biggest > $size[0]) {
            return \str_replace("{$size[0]}px", "{$biggest}px", $sizes);
        }
        
        return $sizes;
    }

    /**
     * Generate a dynamic image.
     *
     * Snap tries to save server space by only generating images needed for admin use.
     * All other theme images are generated dynamically by this method.
     *
     * @since  1.0.0
     *
     * @param mixed        $image Image array to pass on from this filter.
     * @param int          $id   Attachment ID for image.
     * @param array|string $size Optional. Image size to scale to. Accepts any valid image size,
     *                           or an array of width and height values in pixels (in that order).
     *                           Default 'medium'.
     * @return false|array Array containing the image URL, width, height, and boolean for whether
     *                     the image is an intermediate size. False on failure.
     */
    public function generateDynamicImage($image, $id, $size)
    {
        if ('full' == $size) {
            return $image;
        }

        return $this->image_service->generateDynamicImage($image, $id, $size);
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
    public function placeholderImageFallback($html, $post_id, $post_thumbnail_id, $size, $attr)
    {
        if ($html === '' && Config::get('images.placeholder_dir') !== false) {
            $html = $this->image_service->getPlaceholderImage($post_id, $post_thumbnail_id, $size, $attr);
        }

        return $html;
    }

    /**
     * Adds any extra sizes to add media sizes dropdown.
     *
     * @since  1.0.0
     *
     * @param  array $sizes Current sizes for inclusion.
     * @return array Altered $sizes
     */
    public function enableCustomImageSizes($sizes)
    {
        // Merge custom sizes into $sizes.
        $sizes = \array_merge($sizes, $this->size_dropdown_names);

        // Ensure 'Full size' is always at end.
        unset($sizes['full']);
        unset($sizes['thumbnail']);

        if (Config::get('images.insert_image_allow_full_size') || empty($sizes)) {
            $sizes['full'] = 'Full Size';
        }

        return $sizes;
    }
    
    public function max_srcset_image_width()
    {
        return 9999;
    }

    /**
     * Returns the image quality option.
     *
     * @since  1.0.0
     *
     * @param  int $quality Existing value.
     * @return int A number between 0-100.
     */
    public function getUploadQuality($quality)
    {
        if (\is_numeric(Config::get('images.default_image_quality'))) {
            return (int) Config::get('images.default_image_quality');
        }

        return $quality;
    }

    /**
     * Removes all built in image sizes, leaving only full and thumbnail.
     *
     * @since  1.0.0
     *
     * @param  array $sizes Current registered sizes.
     * @return array Modified $sizes array.
     */
    public function removeDefaultImageSizes($sizes = [])
    {
        $user_sizes_to_remove = \collect(Config::get('images.image_sizes'))
            ->filter(
                function ($value) {
                    return $value === false;
                }
            )
            ->all();

        $user_sizes_to_remove = \array_keys($user_sizes_to_remove);

        $sizes_to_remove = \array_merge($user_sizes_to_remove, self::DEFAULT_IMAGE_SIZES);

        if (\is_array(\current($sizes))) {
            $sizes = \array_keys($sizes);
        }

        return \array_diff($sizes, $sizes_to_remove);
    }

    /**
     * Removes all custom image sizes that do not have dropdown names.
     *
     * This allows developers to specify which image sizes are choosable within an editor context, and which
     * should only be generated if actually needed.
     *
     * @since  1.0.0
     *
     * @param  array $sizes Current registered sizes.
     * @return array Modified $sizes array.
     */
    public function removeCustomImageSizes($sizes = [])
    {
        if (Config::get('images.dynamic_image_sizes') !== false) {
            return \array_diff_key($sizes, self::$dynamic_sizes);
        }

        return $sizes;
    }

    /**
     * Sets the default selected option of the insert image size dropdown.
     *
     * Defaults to medium_large.
     * Also sets default alignment to center.
     *
     * @since  1.0.0
     */
    public function setInsertImageDefaultSize()
    {
        \update_option('image_default_align', 'center');
        \update_option('image_default_size', Config::get('images.insert_image_default_size'));
    }

    /**
     * Set the dynamic_sizes array.
     *
     * Adds image sizes declared in config that are not usable in the editor as dynamic sizes.
     *
     * @since  1.0.0
     */
    private function setDynamicSizes()
    {
        foreach (Config::get('images.image_sizes') as $size => $data) {
            if (! isset($data[3]) || ! $data[3]) {
                self::$dynamic_sizes[ $size ] = true;
            }
        }

        if (! empty(Config::get('images.dynamic_image_sizes'))) {
            foreach (Config::get('images.dynamic_image_sizes') as $size => $data) {
                self::$dynamic_sizes[ $size ] = true;
            }
        }
    }

    /**
     * Enabled theme support for thumbnails.
     *
     * Uses the value of Config::get( 'images.supports_featured_images' ) enable thumbnails for all post types or a select few.
     *
     * @since  1.0.0
     */
    private function enableThumbnailSupport()
    {
        $enabled_thumbnails = Config::get('images.supports_featured_images');

        if (! empty($enabled_thumbnails)) {
            if (\is_array($enabled_thumbnails)) {
                \add_theme_support('post-thumbnails', $enabled_thumbnails);
            } elseif ($enabled_thumbnails === true) {
                \add_theme_support('post-thumbnails');
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
    private function registerImageSizes()
    {
        // No image sizes found.
        if (empty(Config::get('images.image_sizes')) && empty(Config::get('images.dynamic_image_sizes'))) {
            return;
        }

        $sizes = Config::get('images.image_sizes');

        // Merge in dynamic sizes if found
        if (! empty(Config::get('images.dynamic_image_sizes'))) {
            $sizes = \array_merge($sizes, Config::get('images.dynamic_image_sizes'));
        }

        // Loop through sizes.
        foreach ($sizes as $name => $size_info) {
            // Get size properties with basic fallbacks.
            $width = (int) isset($size_info[0]) ? $size_info[0] : 0;
            $height = (int) isset($size_info[1]) ? $size_info[1] : 0;
            $crop = isset($size_info[2]) ? $size_info[2] : false;

            if (\in_array($name, self::DEFAULT_IMAGE_SIZES)) {
                if ($size_info !== false) {
                    // Set other built-in sizes.
                    \update_option($name . '_size_w', $width);
                    \update_option($name . '_size_h', $height);
                    \update_option($name . '_crop', $crop);
                } else {
                    $callback = function ($sizes = []) use ($name) {
                        if (!\is_string(\current($sizes))) {
                            return $sizes;
                        }

                        return \array_diff($sizes, [$name]);
                    };

                    // Remove the size.
                    $this->addFilter('intermediate_image_sizes_advanced', $callback);
                    $this->addFilter('intermediate_image_sizes', $callback);
                }
            } else {
                // Add custom image size.
                \add_image_size($name, $width, $height, $crop);
            }

            // If a custom dropdown name has been defined.
            if (isset($size_info[3]) && ! empty($size_info[3])) {
                $this->size_dropdown_names[ $name ] = $size_info[3];
            }
        }
    }
}
