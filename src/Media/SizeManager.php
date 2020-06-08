<?php

namespace Snap\Media;

use Snap\Core\Hookable;
use Snap\Services\Config;
use Tightenco\Collect\Support\Arr;

class SizeManager extends Hookable
{
    /**
     * Default WordPress image sizes.
     *
     * @var array
     */
    public const DEFAULT_IMAGE_SIZES = [
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
     * Holds any disabled default sizes.
     *
     * @var array
     */
    private $disabled_default_sizes = [];

    /**
     * Holds any defined image dropdown names.
     *
     * @var array
     */
    public $size_dropdown_names = [];

    /**
     * @var ImageService
     */
    private $image_service;

    /*
     * Inject Image_Service
     *
     * @param ImageService $image_service
     */
    public function __construct(ImageService $image_service)
    {
        $this->image_service = $image_service;
        $this->setDynamicSizes();
    }

    /**
     * Register class conditional filters.
     */
    public function boot(): void
    {
        $this->addFilter('wp_editor_set_quality', 'getUploadQuality');
        $this->addFilter('intermediate_image_sizes_advanced', 'removeCustomImageSizes');
        $this->addFilter('max_srcset_image_width', 'maxSrcsetImageWidth');
        $this->addFilter('wp_calculate_image_sizes', 'updateMaxSizeAttrInSrcsetSizeAttr');

        // Enable post-thumbnail support.
        $this->enableThumbnailSupport();

        // Register all image sizes.
        $this->registerImageSizes();

        if (!empty($this->size_dropdown_names)) {
            $this->addFilter('image_size_names_choose', 'enableCustomImageSizes');
        }

        $this->addFilter('acf/get_image_sizes', 'acfImageSizes');

        // Set default image size dropdown value.
        if (!empty(Config::get('images.insert_image_default_size'))) {
            $this->addFilter('after_setup_theme', 'setInsertImageDefaultSize');
        }

        if (Config::get('images.dynamic_image_sizes') !== false) {
            $this->addFilter('image_downsize', 'generateDynamicImage');
        }
    }

    /**
     * Deletes dynamic images by size name.
     *
     * Limited to 25 at a time.
     *
     * @param array $sizes Size to delete.
     * @return bool|int Deleted image count or true if none left.
     */
    public static function deleteDynamicImageBySizeAjax($sizes)
    {
        global $wpdb;
        $sizes = Arr::wrap($sizes);

        $args = [];
        $query = "SELECT post_id FROM $wpdb->postmeta WHERE `meta_key` = '_wp_attachment_metadata'";

        foreach ($sizes as $n => $size) {
            $query .= $n === 0 ? ' AND `meta_value` LIKE %s' : ' OR `meta_value` LIKE %s';
            $args[] = "%\"{$size}\"%";
        }

        $query .= ' LIMIT 25';

        $images = $wpdb->get_col(
            $wpdb->prepare($query, $args)
        );

        if (empty($images)) {
            return true;
        }

        foreach ($images as $id) {
            self::deleteDynamicImagesForAttachment($sizes, $id);
        }

        if (\count($images) < 25) {
            return true;
        }

        return \count($images);
    }

    /**
     * Returns the amount of images using the provided $sizes.
     *
     * @param $sizes
     * @return null|string
     */
    public static function getCountForSize($sizes): ?string
    {
        global $wpdb;
        $sizes = Arr::wrap($sizes);
        $args = [];

        $query = "SELECT COUNT(*) FROM $wpdb->postmeta WHERE `meta_key` = '_wp_attachment_metadata'";

        foreach ($sizes as $n => $size) {
            $query .= $n === 0 ? ' AND `meta_value` LIKE %s' : ' OR `meta_value` LIKE %s';
            $args[] = "%\"{$size}\"%";
        }

        return $wpdb->get_var($wpdb->prepare($query, $args)) ?? '0';
    }

    /**
     * Deletes dynamic sizes for a given attachment id.
     *
     * @param array $sizes         Sizes to delete.
     * @param int   $attachment_id Attachment ID.
     */
    public static function deleteDynamicImagesForAttachment(array $sizes, int $attachment_id): void
    {
        $meta = \wp_get_attachment_metadata($attachment_id);
        $dir = \pathinfo(get_attached_file($attachment_id), PATHINFO_DIRNAME);

        foreach ($sizes as $size) {
            if (isset($meta['sizes'][$size])) {
                $file = $meta['sizes'][$size]['file'];

                // Remove size meta from attachment
                unset($meta['sizes'][$size]);

                \wp_delete_file_from_directory(\trailingslashit($dir) . $file, $dir);

                \do_action('snap_deleted_dynamic_image', $attachment_id);

                \wp_update_attachment_metadata($attachment_id, $meta);
            }
        }
    }

    /**
     * Return the theme dynamic image sizes.
     *
     * @return array
     */
    public static function getDynamicSizes(): array
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
     * @param int          $id    Attachment ID for image.
     * @param array|string $size  Optional. Image size to scale to. Accepts any valid image size,
     *                            or an array of width and height values in pixels (in that order).
     *                            Default 'medium'.
     * @return false|array Array containing the image URL, width, height, and boolean for whether
     *                            the image is an intermediate size. False on failure.
     */
    public function generateDynamicImage($image, $id, $size)
    {
        if ('full' === $size) {
            return $image;
        }

        $image = $this->image_service->generateDynamicImage($image, $id, $size);

        // Let WP know to treat our dynamic size as an intermediate size.
        if (isset($image[3])) {
            $image[3] = true;
        }

        return $image;
    }

    /**
     * Adds any extra sizes to add media sizes dropdown.
     *
     * @param array $sizes Current sizes for inclusion.
     * @return array Altered $sizes
     */
    public function enableCustomImageSizes($sizes): array
    {
        // Merge custom sizes into $sizes.
        $sizes = \array_merge($sizes, $this->size_dropdown_names);

        foreach ($sizes as $size => $name) {
            if ($this->isSizeDisabled($size)) {
                unset($sizes[$size]);
            }
        }

        // Ensure 'Full size' is always at end.
        unset($sizes['full']);

        if (!\defined('DOING_AJAX') || DOING_AJAX === false) {
            unset($sizes['thumbnail']);
        }

        if (empty($sizes) || Config::get('images.insert_image_allow_full_size')) {
            $sizes['full'] = 'Original Size';
        }

        return $sizes;
    }

    /**
     * Rewrite srcset to always have the biggest size last and kicking in above 1920 width by default.
     *
     * @param array  $sizes
     * @param string $size
     * @param string $image_src
     * @param array  $image_meta
     * @return mixed
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function updateMaxSizeAttrInSrcsetSizeAttr($sizes, $size, $image_src, $image_meta)
    {
        $biggest = $size[0];

        if (isset($image_meta['sizes']) && !empty($image_meta['sizes'])) {
            foreach ($image_meta['sizes'] as $key => $s) {
                if (!isset($s['width'])) {
                    continue;
                }

                if ($s['width'] > $biggest
                    && \wp_image_matches_ratio($size[0], $size[1], $s['width'], $s['height'])
                ) {
                    $biggest = $s['width'];
                }
            }
        }

        if ($biggest > $size[0]) {
            // return \str_replace("{$size[0]}px", "{$biggest}px", $sizes);
            return \str_replace('100vw,', "100vw, (min-width: 1921px) {$biggest}px,", $sizes);
        }

        return $sizes;
    }

    /**
     * Ensure that there is no effective max srcset image size.
     *
     * @return int
     */
    public function maxSrcsetImageWidth(): int
    {
        return 9999;
    }

    /**
     * Returns the image quality option.
     *
     * // TODO move somewhere else
     *
     * @param int $quality Existing value.
     * @return int A number between 0-100.
     */
    public function getUploadQuality(int $quality): int
    {
        if (\is_numeric(Config::get('images.default_image_quality'))) {
            return (int)Config::get('images.default_image_quality');
        }

        return $quality;
    }

    /**
     * Removes all custom image sizes that do not have dropdown names.
     *
     * This allows developers to specify which image sizes are choosable within an editor context, and which
     * should only be generated if actually needed.
     *
     * @param array $sizes Current registered sizes.
     * @return array Modified $sizes array.
     */
    public function removeCustomImageSizes(array $sizes = []): array
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
    public function setInsertImageDefaultSize(): void
    {
        \update_option('image_default_align', 'center');
        \update_option('image_default_size', Config::get('images.insert_image_default_size'));
    }

    /**
     * Sets ACF available image sizes and removes disabled sizes.
     *
     * @hook acf/get_image_sizes
     *
     * @param array $sizes
     * @return array
     */
    public function acfImageSizes(array $sizes): array
    {
        return \array_merge(\array_diff_key($sizes, $this->disabled_default_sizes), $this->size_dropdown_names);
    }

    /**
     * Set the dynamic_sizes array.
     *
     * Adds image sizes declared in config that are not usable in the editor as dynamic sizes.
     */
    private function setDynamicSizes(): void
    {
        foreach (Config::get('images.image_sizes') as $size => $data) {
            if (!isset($data[3]) || !$data[3]) {
                self::$dynamic_sizes[$size] = true;
            }
        }

        if (!empty(Config::get('images.dynamic_image_sizes'))) {
            foreach (Config::get('images.dynamic_image_sizes') as $size => $data) {
                self::$dynamic_sizes[$size] = true;
            }
        }
    }

    /**
     * Enabled theme support for thumbnails.
     *
     * Uses the value of Config::get('images.supports_featured_images') enable thumbnails for all post types or a
     * select few.
     */
    private function enableThumbnailSupport(): void
    {
        $enabled_thumbnails = Config::get('images.supports_featured_images');

        if (!empty($enabled_thumbnails)) {
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
    private function registerImageSizes(): void
    {
        // No image sizes found.
        if (empty(Config::get('images.image_sizes')) && empty(Config::get('images.dynamic_image_sizes'))) {
            return;
        }

        $sizes = Config::get('images.image_sizes');

        // Merge in dynamic sizes if found
        if (!empty(Config::get('images.dynamic_image_sizes'))) {
            $sizes = \array_merge($sizes, Config::get('images.dynamic_image_sizes'));
        }

        // If thumbnail has not been overwritten, then define it as default post-thumbnail size.
        if (!isset($sizes['thumbnail']) && !isset($sizes['post-thumbnail'])) {
            $sizes['thumbnail'] = [266, 266, true];
        }

        // Loop through sizes.
        foreach ($sizes as $name => $size_info) {
            // Get size properties with basic fallback.
            $width = (int)isset($size_info[0]) ? $size_info[0] : 0;
            $height = (int)isset($size_info[1]) ? $size_info[1] : 0;
            $crop = $size_info[2] ?? false;

            if ($size_info === false || \in_array($name, self::DEFAULT_IMAGE_SIZES, true)) {
                if ($size_info !== false) {
                    // Set other built-in sizes.
                    $saved_value = \snap_get_image_size($name);

                    if ($saved_value['crop'] !== $crop) {
                        \update_option($name . '_crop', $crop, true);
                    }

                    if ((int)$saved_value['width'] !== (int)$width) {
                        \update_option($name . '_size_w', $width, true);
                    }

                    if ((int)$saved_value['height'] !== (int)$height) {
                        \update_option($name . '_size_h', $height, true);
                    }
                } else {
                    $callback = static function ($sizes = []) use ($name) {
                        if (!\is_string(\current($sizes))) {
                            return $sizes;
                        }

                        return \array_diff($sizes, [$name]);
                    };

                    $this->disabled_default_sizes[$name] = $name;

                    // Remove the size.
                    $this->addFilter('intermediate_image_sizes_advanced', $callback);
                    $this->addFilter('intermediate_image_sizes', $callback);

                    // Since 5.3, WP includes extra high res sizes for medium and large.
                    if ($name === 'medium') {
                        \remove_image_size('1536x1536');
                        $this->disabled_default_sizes['1536x1536'] = '1536x1536';
                    }

                    if ($name === 'large') {
                        \remove_image_size('2048x2048');
                        $this->disabled_default_sizes['2048x2048'] = '2048x2048';
                    }
                }
            } else {
                // Add custom image size.
                \add_image_size($name, $width, $height, $crop);
            }

            // If a custom dropdown name has been defined.
            if (isset($size_info[3]) && !empty($size_info[3])) {
                $this->size_dropdown_names[$name] = $size_info[3];
            }
        }
    }

    /**
     * Check if a given size has been disabled via config/images.php.
     *
     * @param string $size Size to check.
     * @return bool
     */
    private function isSizeDisabled(string $size): bool
    {
        return isset($this->disabled_default_sizes[$size]);
    }
}
