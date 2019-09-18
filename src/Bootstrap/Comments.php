<?php

namespace Snap\Bootstrap;

use Snap\Core\Hookable;
use Snap\Services\Config;
use Snap\Utils\Theme;

/**
 * All Comment functionality.
 */
class Comments extends Hookable
{
    /**
     * Adds optional filters if required.
     */
    public function boot()
    {
        if (Config::get('theme.disable_comments') === false) {
            $this->addFilter('comments_template', 'mapCommentsTemplateToPartials');
        }
    }

    /**
     * Attempt to map the file passes into comments_template to a partial.
     *
     * @param string $path
     * @return string
     */
    public function mapCommentsTemplateToPartials($path)
    {
        $tpl = \str_replace(
            [STYLESHEETPATH, TEMPLATEPATH, '.'],
            ['', '', '/'],
            $path
        );

        $locate = \locate_template(Theme::getPartialPath($tpl), false);

        if ($locate !== '') {
            return $locate;
        }

        $locate = \locate_template(Theme::getPartialPath('comments'), false);

        if ($locate !== '') {
            return $locate;
        }

        return $path;
    }
}
