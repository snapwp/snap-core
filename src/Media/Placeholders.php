<?php

namespace Snap\Media;

use Snap\Core\Hookable;
use Snap\Services\Config;
use Snap\Utils\Image;
use Snap\Utils\Theme;

class Placeholders extends Hookable
{
    /**
     * The file extensions to check when finding placeholders.
     *
     * @var array
     */
    protected $placeholder_extensions = [];

    /**
     * The placeholder directory path.
     *
     * @var array
     */
    protected $placeholder_directory = '';

    /**
     * The placeholder directory path URI.
     *
     * @var array
     */
    protected $placeholder_directory_uri = '';

    /**
     * Register class conditional filters.
     */
    public function __construct()
    {
        /**
         * The file extensions to search for when looking for placeholder fallback images.
         *
         * @param array $extensions The file extension list, in order of search preference.
         * @return array $extensions The modified file extension list.
         */
        $this->placeholder_extensions = \apply_filters('snap_placeholder_img_extensions', ['.jpg', '.svg', '.png']);

        $this->placeholder_directory = Theme::getActiveThemePath(
            \trailingslashit(Config::get('images.placeholder_dir'))
        );

        $this->placeholder_directory_uri = Theme::getActiveThemeUri(
            \trailingslashit(Config::get('images.placeholder_dir'))
        );
    }

    /**
     * Register class hooks.
     */
    public function boot(): void
    {
        if (Config::get('images.placeholder_dir') !== false) {
            $this->addFilter('post_thumbnail_html', 'placeholderImageFallback');
            $this->addFilter('image_downsize', 'catchInvalidAttachmentId');
            $this->addFilter('wp_get_attachment_image_attributes', 'placeholderAttributes');
        }
    }

    /**
     * If no post_thumbnail was found, find the corresponding placeholder image and return the image HTML.
     *
     * @param string       $html               The post thumbnail HTML.
     * @param int          $post_id            The post ID.
     * @param string       $post_thumbnail_id  The post thumbnail ID.
     * @param string|array $size               The post thumbnail size. Image size or array of width and height
     *                                         values (in that order). Default 'post-thumbnail'.
     * @param string       $attr               Query string of attributes.
     * @return string The image HTML
     */
    public function placeholderImageFallback($html, $post_id, $post_thumbnail_id, $size, $attr): string
    {
        if ($html === '') {
            $html = $this->getPlaceholderImage($post_id, $post_thumbnail_id, $size, $attr);
        }

        return $html;
    }

    /**
     * Set attributes on placeholder images when using wp_get_attachment_image.
     *
     * @param array $attr
     * @return array
     */
    public function placeholderAttributes(array $attr): array
    {
        if ($attr['alt'] === '' && strpos($attr['src'], Config::get('images.placeholder_dir')) !== false) {
            $attr['alt'] = get_bloginfo('name') . ' placeholder image';
            $attr['aria-hidden'] = 'true';
        }

        return $attr;
    }

    /**
     * Injects placeholders into wp_get_attachment_image.
     *
     * @param array|false        $default
     * @param int                $id
     * @param string|array|false $size
     * @return array
     */
    public function catchInvalidAttachmentId($default, $id, $size)
    {
        $img_url = wp_get_attachment_url($id);
        $meta = wp_get_attachment_metadata($id);

        if ($img_url === false && empty($meta)) {
            $placeholder_url = $this->findPlaceholder($size);

            if ($placeholder_url !== false) {
                return [
                    $placeholder_url,
                    Image::getImageWidth($size),
                    Image::getImageHeight($size),
                    true
                ];
            }
        }

        return $default;
    }

    /**
     * Searches for a suitable placeholder fallback image.
     *
     * First checks placeholder-${image_size}, then placeholder-${post_type}, then finally placeholder.
     *
     * Runs through $this->placeholder_extensions in order when searching for placeholders.
     *
     * @param int|false    $post_id            The post ID.
     * @param string       $post_thumbnail_id  The post thumbnail ID.
     * @param string|array $size               The post thumbnail size. Image size or array of width and height
     *                                         values (in that order). Default 'post-thumbnail'.
     * @param array        $attr               Array string of attributes.
     * @return string The image HTML
     */
    private function getPlaceholderImage($post_id, $post_thumbnail_id, $size, $attr = []): string
    {
        $original_size = $size;

        $post_id = $post_id ?? \get_the_id();

        $placeholder_url = $this->findPlaceholder($size, $post_id);

        if ($placeholder_url !== false) {
            $html = \sprintf(
                /** @lang text */
                '<img src="%s" alt="%s" width="%d" height="%d" aria-hidden="true" %s>',
                $placeholder_url,
                get_bloginfo('name') . ' placeholder image',
                \is_array($original_size) ? $original_size[0] : Image::getImageWidth($size),
                \is_array($original_size) ? $original_size[1] : Image::getImageHeight($size),
                $this->parseAttributes($attr)
            );

            /**
             * Filter the placeholder image HTML.
             *
             * @param string $output The HTML output for the placeholder image tag.
             * @return string $output The HTML output for the placeholder image tag.
             */
            return \apply_filters('snap_placeholder_img_html', $html);
        }

        return '';
    }

    /**
     * Search for the different placeholder image names in order.
     *
     * @param string $size
     * @param null   $post_id
     * @return string|false
     */
    private function findPlaceholder($size, $post_id = null)
    {
        if (Image::getImageSize($size) === false) {
            $size = 'full';
        }

        // Search for a size specific placeholder first.
        $placeholder_url = $this->searchForPlaceholder('placeholder-' . $size);

        // Then the post type placeholder.
        if ($placeholder_url === false) {
            $placeholder_url = $this->searchForPlaceholder('placeholder-' . get_post_type($post_id));
        }

        // Finally a generic placeholder.
        if ($placeholder_url === false) {
            $placeholder_url = $this->searchForPlaceholder('placeholder');
        }

        if (empty($placeholder_url)) {
            return false;
        }

        return $placeholder_url;
    }

    /**
     * Scans the file system to see if a given file exists with an extension from $this->placeholder_extensions.
     *
     * @param string $file_name The placeholder to look for, minus extension.
     * @return string|bool false if not found, otherwise the public URI to the found placeholder.
     */
    private function searchForPlaceholder($file_name)
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
     * @param array|string $attr The $attr array.
     * @return string
     */
    private function parseAttributes($attr): string
    {
        $html = '';

        if (!empty($attr)) {
            $html = '';

            foreach ($attr as $key => $value) {
                $html .= \sprintf('%s="%s" ', $key, \esc_attr($value));
            }
        }

        return \trim($html);
    }
}
