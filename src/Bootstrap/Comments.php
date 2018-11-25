<?php

namespace Snap\Bootstrap;

use Snap\Core\Hookable;
use Snap\Services\Config;
use Snap\Utils\Theme_Utils;

/**
 * All Comment functionality.
 *
 * @since  1.0.0
 */
class Comments extends Hookable
{
    /**
     * Adds optional filters if required.
     *
     * @since 1.0.0
     */
    public function boot()
    {
        if (Config::get('theme.disable_comments') === false) {
            $this->add_filter('comments_template', 'map_comments_template_to_partials');
        }
    }

    /**
     * Attempt to map the file passes into comments_template to a partial.
     *
     * @param string $path
     * @return string
     */
    public function map_comments_template_to_partials($path)
    {
        $tpl = \str_replace(
            [STYLESHEETPATH, TEMPLATEPATH, '.'],
            ['', '', '/'],
            $path
        );

        $locate = \locate_template(Theme_Utils::get_path_to_partial($tpl), false);

        if ($locate !== '') {
            return $locate;
        }

        $locate = \locate_template(Theme_Utils::get_path_to_partial('comments'), false);

        if ($locate !== '') {
            return $locate;
        }

        return $path;
    }
}
