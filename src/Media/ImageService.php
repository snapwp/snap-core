<?php

namespace Snap\Media;

use Snap\Services\Config;
use Snap\Utils\Image;
use Snap\Utils\Theme;

class ImageService
{
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
        global $_wp_additional_image_sizes;

        if (!\wp_attachment_is_image($id)) {
            return $image;
        }

        // Get parent image meta data.
        $meta = \wp_get_attachment_metadata($id);

        // Very rarely the image has no meta - Like if added via fakerpress. Bail early.
        if (!isset($meta['file'])) {
            return $image;
        }

        if (\is_array($size)) {
            list($width, $height) = $size;

            if ($meta['width'] < $width) {
                $width = $meta['width'];
                $size[0] = $width;
            }

            if ($meta['height'] < $height) {
                $height = $meta['height'];
                $size[1] = $height;
            }

            $crop = !\wp_image_matches_ratio($meta['height'], $meta['width'], $height, $width);
        } else {
            // Short-circuit if $size has not been registered.
            if (!isset($_wp_additional_image_sizes[$size])) {
                return $image;
            }

            $width = $_wp_additional_image_sizes[$size]['width'];
            $height = $_wp_additional_image_sizes[$size]['height'];
            $crop = $_wp_additional_image_sizes[$size]['crop'];
        }

        $check = \image_get_intermediate_size($id, $size);

        // Bail early if we can.
        if ($check !== false) {
            return [$check['url'], $check['width'], $check['height'], false];
        }

        $parent_image_path = apply_filters(
            'snap_dynamic_image_source',
            \wp_upload_dir()['basedir'] . '/' . $meta['file'],
            $id
        );

        if ($check === false || !\file_exists(\wp_upload_dir()['basedir'] . '/' . $check['path'])) {
            $update = false;

            if (\is_array($size)) {
                $new_meta = \image_make_intermediate_size($parent_image_path, $width, $height, $crop);

                if ($new_meta !== false) {
                    $meta['sizes'][ \implode('x', [$width, $height]) ] = $new_meta;
                    $update = true;
                }
            }

            if ($update === false) {
                foreach ($_wp_additional_image_sizes as $key => $size_data) {
                    if (\array_key_exists($key, $meta['sizes']) === true) {
                        continue;
                    }

                    if ($key == $size) {
                        /*
                         * Generate the requested dynamic size.
                         */
                        $new_meta = \image_make_intermediate_size($parent_image_path, $width, $height, $crop);

                        if ($new_meta === false) {
                            continue;
                        }

                        $meta['sizes'][ $size ] = $new_meta;

                        \do_action('snap_dynamic_image_meta', $size, $meta, $id);

                        $update = true;
                    } elseif (\wp_image_matches_ratio($size_data['width'], $size_data['height'], $width, $height)) {
                        /*
                         * This size is has not been requested, but matches the requested size ratio so should be generated
                         * for use within the srcset.
                         */
                        $new_meta = \image_make_intermediate_size(
                            $parent_image_path,
                            $size_data['width'],
                            $size_data['height'],
                            $size_data['crop']
                        );

                        if ($new_meta === false) {
                            continue;
                        }

                        $meta['sizes'][ $key ] = $new_meta;

                        \do_action('snap_dynamic_image_meta', $size, $meta, $id);

                        $update = true;
                    }
                }
            }

            if ($update === true) {
                \wp_update_attachment_metadata($id, $meta);
            }
        }

        return $image;
    }
}
