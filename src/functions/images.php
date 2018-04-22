<?php
/**
 * Image related theme functions and helpers.
 *
 * Includeds functions to easily get registered image sizes,
 * and replacements for post_thumbnail functions with an automatic placeholder image fallback.
 *
 * @package Snap\Functions
 * @version  1.0.0
 */

// If this file is called directly, abort.
if (! defined('ABSPATH')) {
    die('Direct access is forbidden.');
}

/**
 * Get size information for all currently registered image sizes.
 *
 * @global $_wp_additional_image_sizes
 *
 * @since  1.0.0
 *
 * @return array Data for all currently registered image sizes.
 */
function snap_get_image_sizes()
{
    global $_wp_additional_image_sizes;

    $sizes = [];

    foreach (get_intermediate_image_sizes() as $size) {
        if (in_array($size, ['thumbnail', 'medium', 'medium_large', 'large'])) {
            $sizes[ $size ] = [
                'width' => get_option("{$size}_size_w"),
                'height' => get_option("{$size}_size_h"),
                'crop' => (bool) get_option("{$size}_crop"),
            ];
        } elseif (isset($_wp_additional_image_sizes[ $size ])) {
            $sizes[ $size ] = [
                'width'  => $_wp_additional_image_sizes[ $size ]['width'],
                'height' => $_wp_additional_image_sizes[ $size ]['height'],
                'crop'   => $_wp_additional_image_sizes[ $size ]['crop'],
            ];
        }
    }

    return $sizes;
}


/**
 * Get size information for a specific image size.
 *
 * @since  1.0.0
 *
 * @param  string $size The image size for which to retrieve data.
 * @return bool|array Size data about an image size or false if the size doesn't exist.
 */
function snap_get_image_size($size)
{
    $sizes = snap_get_image_sizes();

    if (is_string($size) && isset($sizes[ $size ])) {
        return $sizes[ $size ];
    }

    return false;
}


/**
 * Get the width of a specific image size.
 *
 * Really useful for HTML img tags.
 *
 * @since  1.0.0
 *
 * @param  string $size The image size for which to retrieve data.
 * @return bool|int Width of an image size or false if the size doesn't exist.
 */
function snap_get_image_width($size)
{
    // Get the size meta array.
    $size = snap_get_image_size($size);

    if ($size !== false) {
        return false;
    }

    if (isset($size['width'])) {
        return (int) $size['width'];
    }

    return false;
}


/**
 * Get the height of a specific image size.
 *
 * Really useful for HTML img tags.
 *
 * @since  1.0.0
 *
 * @param  string $size The image size for which to retrieve data.
 * @return bool|int Height of an image size or false if the size doesn't exist.
 */
function snap_get_image_height($size)
{
    // Get the size meta array.
    $size = snap_get_image_size($size);

    if ($size !== false) {
        return false;
    }

    if (isset($size['height'])) {
        return (int) $size['height'];
    }

    return false;
}


/**
 * Renders a post thumbnail to the page.
 *
 * If the post has no featured image, falls back to placeholder-{size}, then placeholder-{post_type}, then to placeholder.
 * If a placeholder is used, the width and height will be set according to the registered dimensions for the given $size.
 *
 * @since  1.0.0
 *
 * @param  string $size The registered size of image to find
 * @param  array  $args Array of attrs => values to apply to the image
 */
function snap_the_post_thumbnail($size = 'full', $args = [])
{
    echo snap_get_the_post_thumbnail(get_the_id(), $size, $args);
}


/**
 * Returns the post thumbnail img element.
 *
 * If the post has no featured image, falls back to placeholder-{size}, then placeholder-{post_type}, then to placeholder.
 * If a placeholder is used, the width and height will be set according to the registered dimensions for the given $size.
 *
 * @since  1.0.0
 *
 * @param  int|object $post  The post ID or object to find the image for
 * @param  string     $size  The registered size of image to find
 * @param  array      $args  Array of attrs => values to apply to the image
 * @return string The image HTML
 */
function snap_get_the_post_thumbnail($post, $size = 'full', $args = [])
{
    // If the size provided doesn't exist, default to full.
    if (is_string($size) && snap_get_image_size($size) === false) {
        $size = 'full';
    }

    // Check we have a post thumbnail.
    if (has_post_thumbnail($post)) {
        return get_the_post_thumbnail($post, $size, $args);
    }

    $placeholder_src = snap_get_the_post_thumbnail_url($post, $size);

    if ($placeholder_src === false) {
        return false;
    }

    $output = sprintf(
        '<img src="%s" alt="%s" width="%s" height="%s" %s>',
        $placeholder_src,
        get_the_title($post),
        snap_get_image_width($size),
        snap_get_image_height($size),
        ( isset($args['class']) ? 'class="' . $args['class'] . '"' : '' )
    );

    /**
     * Filter the placeholder image HTML.
     *
     * @since  1.0.0
     *
     * @param string $output The HTML output for the placeholder image tag.
     * @return string $output The HTML output for the placeholder image tag.
     */
    return apply_filters('snap_placeholder_img_html', $output);
}


/**
 * Returns the post thumbnail URL.
 *
 * If the post has no featured image, falls back to placeholder-{size}, then placeholder-{post_type}, then to placeholder.
 * If a placeholder is used, the width and height will be set according to the registered dimensions for the given $size.
 *
 * @since  1.0.0
 *
 * @param  int|object $post  The post ID or object to find the image for.
 * @param  string     $size  The registered size of image to find.
 * @return string|bool The image URL or false if none found.
 */
function snap_get_the_post_thumbnail_url($post, $size = 'full')
{
    // If the size provided doesn't exist, default to full.
    if (snap_get_image_size($size) === false) {
        $size = 'full';
    }

    // Check we have a post thumbnail.
    if (has_post_thumbnail($post)) {
        return get_the_post_thumbnail_url($post, $size);
    }

    /**
     * The file extensions to search for when looking for placeholder fallback images.
     *
     * @since  1.0.0
     *
     * @param array $extensions The file extension list, in order of search preference.
     * @return array $extensions The modified file extension list.
     */
    $extensions = apply_filters('snap_placeholder_img_extensions', [ '.jpg', '.svg', '.png' ]);

    // Get the theme defined placeholder image directory path.
    $placeholder_directory = trailingslashit(Loader::get_option('img_placholder_dir'));

    // Get relative path to placeholder folder.
    $base = $placeholder_directory . 'placeholder-' . $size;

    // look for each extension.
    foreach ($extensions as $ext) {
        $file_path = trailingslashit(get_stylesheet_directory()) . $base . $ext;

        if (file_exists($file_path) === true) {
            return trailingslashit(get_stylesheet_directory_uri()) . $base . $ext;
        }
    }

    // If no size img found, search for generic.
    $base = $placeholder_directory . 'placeholder-' . get_post_type($post);

    foreach ($extensions as $ext) {
        $file_path = trailingslashit(get_stylesheet_directory()) . $base . $ext;

        if (file_exists($file_path) === true) {
            return trailingslashit(get_stylesheet_directory_uri()) . $base . $ext;
        }

        $path = false;
    }
    
    // If no specific img found, search for the generic placeholder.
    $base = $placeholder_directory . 'placeholder';

    foreach ($extensions as $ext) {
        // check if the file exists
        $file_path = trailingslashit(get_stylesheet_directory()) . $base . $ext;
        
        if (file_exists($file_path) === true) {
            return trailingslashit(get_stylesheet_directory_uri()) . $base . $ext;
        }
    }

    return false;
}
