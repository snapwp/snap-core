<?php

namespace Snap\Compatibility;

use Snap\Core\Hookable;
use Snap\Services\Container;

class Loader extends Hookable
{
    public function boot()
    {
        if ($this->is_offload_media_present()) {
            Container::resolve(Compat_Offload_Media::class)->run();
        }
    }

    /**
     * Check if WP Offload S3/Offload Media plugin active.
     *
     * @since  1.0.0
     *
     * @return boolean
     */
    private function is_offload_media_present()
    {
        if (isset($GLOBALS['as3cf']) || isset($GLOBALS['as3cfpro_compat_check']) || \class_exists('AS3CF_Compatibility_Check')) {
            return true;
        }

        return false;
    }
}
