<?php

namespace Snap\Admin;

use Snap\Core\Hookable;

/**
 * MultiLingual description
 */
class DisableLazyLoading extends Hookable
{
    public function boot(): void
    {
        $this->addFilter('wp_lazy_loading_enabled', '__return_false');
        $this->addFilter('post_thumbnail_html', 'stripLazyAttribute');

        $this->addFilter('wp_img_tag_add_decoding_attr', '__return_false');
        $this->addFilter('wp_get_attachment_image_attributes', 'removeDecodingAttribute');
    }

    /**
     * Remove loading-lazy from post thumbnails.
     */
    public function stripLazyAttribute(string $content): string
    {
        return str_replace('loading="lazy"', '', $content);
    }

    /**
     * Remove decoding attribute from images.
     */
    public function removeDecodingAttribute(array $attributes): array
    {
        unset($attributes['decoding']);
        return $attributes;
    }
}
