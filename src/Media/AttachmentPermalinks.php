<?php

namespace Snap\Media;

use Snap\Core\Hookable;
use Snap\Services\Config;

class AttachmentPermalinks extends Hookable
{
    /**
     * Run the Hookable.
     */
    public function boot()
    {
        if (Config::get('images.disable_attachment_permalinks') !== false) {
            $this->addFilter('attachment_link', 'rewriteAttachmentPermalinkToFile');
            $this->addFilter('rewrite_rules_array', 'removeAttachmentRewriteRules');
        }
    }

    /**
     * Remove any reference to attachment URLs.
     *
     * @param string $link Previous link.
     * @param int    $post_id Attachment ID.
     * @return string
     */
    public function rewriteAttachmentPermalinkToFile(string $link, int $post_id): string
    {
        return \wp_get_attachment_url($post_id);
    }

    /**
     * Remove permalink rules with attachment in.
     *
     * @param array $rules Current rules.
     * @return array
     */
    public function removeAttachmentRewriteRules(array $rules): array
    {
        foreach ($rules as $regex => $query) {
            if (\strpos($regex, 'attachment') || \strpos($query, 'attachment')) {
                unset($rules[$regex]);
            }
        }

        return $rules;
    }
}
