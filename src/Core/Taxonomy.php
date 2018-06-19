<?php

namespace Snap\Core;

use PostTypes\Taxonomy as ptt;

class Taxonomy extends Hookable
{
    public $name = null;

    public $plural = null;
    public $singular = null;
    public $slug = null;

    public $labels = [];

    public $options = [];



    public function boot()
    {
        

        $post = new ptt($this->get_names(), $this->options, $this->labels);

        $post->register();
    }

    private function get_names()
    {
        $names = [
            'name' => $this->get_name(),
        ];

        if ($this->plural !== null) {
            $names['plural'] = $this->plural;
        }

        if ($this->singular !== null) {
            $names['singular'] = $this->singular;
        }

        if ($this->slug !== null) {
            $names['slug'] = $this->slug;
        }

        return $names;
    }


    /**
     * Get the unqualified name of the current class and convert it to snake case for the shortcode name.
     *
     * Can be overwritten by setting the $tag property.
     *
     * @since  1.0.0
     *
     * @return string
     */
    private function get_name()
    {
        if ($this->name === null) {
            $classname = \basename(\str_replace(['\\', '_'], ['/', ''], \get_class($this)));
            $classname = \trim(\preg_replace('/([^_])(?=[A-Z])/', '$1_', $classname), '_');

            return \strtolower($classname);
        }

        return $this->name;
    }
}
