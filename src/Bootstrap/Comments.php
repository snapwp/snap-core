<?php

namespace Snap\Bootstrap;

use Bladezero\View\Engines\CompilerEngine;
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
        $this->addFilter('comments_template', 'mapCommentsTemplateToPartials');
    }

    /**
     * Attempt to map the file passes into comments_template to a partial.
     *
     * @param string $path
     * @return string
     */
    public function mapCommentsTemplateToPartials($path)
    {
        $locate = \locate_template(Theme::getPartialPath('comments'), false);

        if ($locate !== '') {
            $compiler = \Snap\Services\Blade::getEngineFromPath($locate);

            if ($compiler instanceof CompilerEngine) {
                // Compile the template
                \Snap\Services\Blade::getEngineFromPath($locate)->getCompiler()->compile($locate);

                // return the compiled path instead of the raw path
                return \Snap\Services\Blade::getEngineFromPath($locate)->getCompiler()->getCompiledPath($locate);
            }

            return $locate;
        }

        return $path;
    }
}
