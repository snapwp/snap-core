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
        $this->addFilter('post_thumbnail_html', 'stripLazyAttr');
    }

    /**
     * Replaces links found in the content with the link markup we want.
     *
     * A bit heavy-handed, but should be fine.
     */
    public function stripLazyAttr(string $content): string
    {
        return str_replace('loading="lazy"', '', $content);
    }
}
