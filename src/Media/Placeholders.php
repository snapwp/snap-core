<?php
namespace Snap\Media;

use Snap\Core\Hookable;
use Snap\Services\Config;

class Placeholders extends Hookable
{
    /**
     * @var Image_Service
     */
    private $image_service;

    /**
     * Inject Image_Service
     *
     * @param Image_Service $image_service
     */
    public function __construct(Image_Service $image_service)
    {
        $this->image_service = $image_service;
    }

    /**
     * Register class hooks.
     */
    public function boot()
    {
        $this->addFilter('post_thumbnail_html', 'placeholderImageFallback');
    }

    /**
     * If no post_thumbnail was found, find the corresponding placeholder image and return the image HTML.
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
}