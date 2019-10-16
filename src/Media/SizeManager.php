<?php

namespace Snap\Media;

use Snap\Core\Hookable;
use Snap\Services\Config;

class SizeManager extends Hookable
{
    /**
     * Default WordPress image sizes.
     *
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
     * @var array
     */
    private static $dynamic_sizes = [];

    /**
     * Holds any defined image dropdown names.
     *
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
     * @var array
     */
    protected $filters = [
        'wp_editor_set_quality' => 'getUploadQuality',
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
     */
    public function boot()
    {
        $this->addFilter('intermediate_image_sizes_advanced', 'removeCustomImageSizes');
        $this->addFilter('max_srcset_image_width', 'maxSrcsetImageWidth');
        $this->addFilter('wp_calculate_image_sizes', 'updateMaxSizeAttrInSrcsetSizeAttr');

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
     * Deletes dynamic images by size name.
     *
     * @param string $size Size to delete.
     */
    public static function deleteDynamicImageBySize(string $size)
    {
        global $wpdb;

        $images = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_id 
                    FROM $wpdb->postmeta
                    WHERE `meta_key` = '_wp_attachment_metadata'
                    AND `meta_value` LIKE %s",
                "%{$size}%"
            )
        );

        if (empty($images)) {
            return;
        }

        foreach ($images as $id) {
            self::deleteDynamicImagesForAttachment([$size], $id);
        }
    }

    /**
     * Deletes dynamic sizes for a given attachment id.
     *
     * @param array $sizes         Sizes to delete.
     * @param int   $attachment_id Attachment ID.
     */
    public static function deleteDynamicImagesForAttachment(array $sizes, int $attachment_id)
    {
        $meta = \wp_get_attachment_metadata($attachment_id);
        $dir = \pathinfo(get_attached_file($attachment_id), PATHINFO_DIRNAME);

        foreach ($sizes as $size) {
            if (isset($meta['sizes'][$size])) {
                $file = $meta['sizes'][$size]['file'];

                // Remove size meta from attachment
                unset($meta['sizes'][$size]);
                \wp_delete_file_from_directory(\trailingslashit($dir) . $file, $dir);
            }
        }

        /**
         * Fires just before 'delete_attachment' is fired when an intermediate image size is deleted via the
         * dynamic sizes admin UI.
         *
         * @param array $sizes List of sizes to be deleted
         * @param int   $id    The ID of the current attachment.
         */
        \do_action('snap_dynamic_image_before_delete', $sizes, $attachment_id);

        \do_action('delete_attachment', $attachment_id);

        /**
         * Fires just after 'delete_attachment' is fired when an intermediate image size is deleted via the
         * dynamic sizes admin UI.
         *
         * @param array $sizes List of sizes to be deleted
         * @param int   $id    The ID of the current attachment.
         */
        \do_action('snap_dynamic_image_after_delete', $sizes, $attachment_id);

        if (\wp_update_attachment_metadata($attachment_id, $meta) === false) {
            // throw summit
        }
    }

    /**
     * Return the theme dynamic image sizes.
     *
     * @return array
     */
    public static function getDynamicSizes()
    {
        return \array_keys(self::$dynamic_sizes);
    }

    /**
     * Generate a dynamic image.
     *
     * Snap tries to save server space by only generating images needed for admin use.
     * All other theme images are generated dynamically by this method.
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
     * Adds any extra sizes to add media sizes dropdown.
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

    public function updateMaxSizeAttrInSrcsetSizeAttr($sizes, $size, $image_src, $image_meta)
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

    public function maxSrcsetImageWidth()
    {
        return 9999;
    }

    /**
     * Returns the image quality option.
     *
     * // TODO move somewhere else
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
     * Removes all custom image sizes that do not have dropdown names.
     *
     * This allows developers to specify which image sizes are choosable within an editor context, and which
     * should only be generated if actually needed.
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
            // Get size properties with basic fallback.
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
